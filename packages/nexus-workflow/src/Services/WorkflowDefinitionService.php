<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nexus\Workflow\Models\WorkflowDefinition;

/**
 * WorkflowDefinitionService
 * 
 * Manages CRUD operations, versioning, and validation for workflow definitions.
 */
class WorkflowDefinitionService
{
    /**
     * Create a new workflow definition.
     * 
     * @param array $data Workflow data including name, key, definition, etc.
     * @return WorkflowDefinition
     * @throws ValidationException
     */
    public function create(array $data): WorkflowDefinition
    {
        // Validate input
        $validated = $this->validateWorkflowData($data);

        // Check if key already exists
        if (WorkflowDefinition::where('key', $validated['key'])->exists()) {
            throw ValidationException::withMessages([
                'key' => ['A workflow with this key already exists. Use createVersion() to add a new version.'],
            ]);
        }

        return DB::transaction(function () use ($validated) {
            // Create first version
            $workflow = WorkflowDefinition::create([
                'name' => $validated['name'],
                'key' => $validated['key'],
                'description' => $validated['description'] ?? null,
                'version' => 1,
                'definition' => $validated['definition'],
                'is_active' => $validated['is_active'] ?? false,
                'created_by' => $validated['created_by'] ?? null,
            ]);

            return $workflow;
        });
    }

    /**
     * Create a new version of an existing workflow.
     * 
     * @param string $key Workflow key
     * @param array $definition New workflow definition
     * @param string|null $createdBy User ID creating the version
     * @param bool $activate Activate this version immediately
     * @return WorkflowDefinition
     * @throws ValidationException
     */
    public function createVersion(string $key, array $definition, ?string $createdBy = null, bool $activate = false): WorkflowDefinition
    {
        // Validate definition structure
        $this->validateDefinitionStructure($definition);

        $latestVersion = WorkflowDefinition::getLatestVersion($key);

        if (!$latestVersion) {
            throw ValidationException::withMessages([
                'key' => ['No workflow found with this key. Use create() to create a new workflow.'],
            ]);
        }

        return DB::transaction(function () use ($latestVersion, $definition, $createdBy, $activate) {
            $newVersion = $latestVersion->createNewVersion($definition, $createdBy);

            if ($activate) {
                $newVersion->activate();
            }

            return $newVersion;
        });
    }

    /**
     * Update an existing workflow definition (only if not active).
     * 
     * @param string $id Workflow ID
     * @param array $data Updated workflow data
     * @return WorkflowDefinition
     * @throws ValidationException
     */
    public function update(string $id, array $data): WorkflowDefinition
    {
        $workflow = WorkflowDefinition::findOrFail($id);

        if ($workflow->is_active) {
            throw ValidationException::withMessages([
                'workflow' => ['Cannot update an active workflow. Create a new version or deactivate first.'],
            ]);
        }

        // Validate input
        $validated = $this->validateWorkflowData($data, $workflow->id);

        return DB::transaction(function () use ($workflow, $validated) {
            $workflow->update([
                'name' => $validated['name'] ?? $workflow->name,
                'description' => $validated['description'] ?? $workflow->description,
                'definition' => $validated['definition'] ?? $workflow->definition,
            ]);

            return $workflow->fresh();
        });
    }

    /**
     * Activate a workflow version.
     * 
     * @param string $id Workflow ID
     * @return WorkflowDefinition
     */
    public function activate(string $id): WorkflowDefinition
    {
        $workflow = WorkflowDefinition::findOrFail($id);

        if (!$workflow->activate()) {
            throw new \RuntimeException('Failed to activate workflow.');
        }

        return $workflow->fresh();
    }

    /**
     * Deactivate a workflow version.
     * 
     * @param string $id Workflow ID
     * @return WorkflowDefinition
     */
    public function deactivate(string $id): WorkflowDefinition
    {
        $workflow = WorkflowDefinition::findOrFail($id);

        return DB::transaction(function () use ($workflow) {
            $workflow->is_active = false;
            $workflow->save();

            return $workflow;
        });
    }

    /**
     * Soft delete a workflow definition.
     * 
     * @param string $id Workflow ID
     * @return bool
     */
    public function delete(string $id): bool
    {
        $workflow = WorkflowDefinition::findOrFail($id);

        if ($workflow->is_active) {
            throw ValidationException::withMessages([
                'workflow' => ['Cannot delete an active workflow. Deactivate it first.'],
            ]);
        }

        return $workflow->delete();
    }

    /**
     * Get active workflow by key.
     * 
     * @param string $key Workflow key
     * @return WorkflowDefinition|null
     */
    public function getActive(string $key): ?WorkflowDefinition
    {
        return WorkflowDefinition::getActiveVersion($key);
    }

    /**
     * Get latest workflow version by key.
     * 
     * @param string $key Workflow key
     * @return WorkflowDefinition|null
     */
    public function getLatest(string $key): ?WorkflowDefinition
    {
        return WorkflowDefinition::getLatestVersion($key);
    }

    /**
     * Get all versions of a workflow.
     * 
     * @param string $key Workflow key
     * @return Collection
     */
    public function getVersions(string $key): Collection
    {
        return WorkflowDefinition::byKey($key)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * List all active workflows.
     * 
     * @return Collection
     */
    public function listActive(): Collection
    {
        return WorkflowDefinition::active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Import workflow from JSON.
     * 
     * @param string $json JSON string
     * @param string|null $createdBy User ID importing the workflow
     * @param bool $activate Activate after import
     * @return WorkflowDefinition
     * @throws ValidationException
     */
    public function importFromJson(string $json, ?string $createdBy = null, bool $activate = false): WorkflowDefinition
    {
        try {
            $workflow = WorkflowDefinition::fromJson($json, $createdBy);

            if ($activate) {
                $workflow->activate();
            }

            return $workflow;
        } catch (\JsonException $e) {
            throw ValidationException::withMessages([
                'json' => ['Invalid JSON format: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Export workflow to JSON.
     * 
     * @param string $id Workflow ID
     * @return string
     */
    public function exportToJson(string $id): string
    {
        $workflow = WorkflowDefinition::findOrFail($id);
        return $workflow->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Validate workflow data.
     * 
     * @param array $data Workflow data
     * @param string|null $excludeId Exclude this ID from unique checks
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateWorkflowData(array $data, ?string $excludeId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string'],
            'definition' => ['required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'created_by' => ['nullable', 'string', 'uuid'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Validate definition structure
        $this->validateDefinitionStructure($validated['definition']);

        return $validated;
    }

    /**
     * Validate workflow definition structure.
     * 
     * Ensures the definition array contains the required keys and valid data.
     * 
     * @param array $definition Workflow definition
     * @throws ValidationException
     */
    protected function validateDefinitionStructure(array $definition): void
    {
        $rules = [
            'states' => ['required', 'array', 'min:1'],
            'states.*' => ['required', 'string'],
            'transitions' => ['required', 'array', 'min:1'],
            'transitions.*.name' => ['required', 'string'],
            'transitions.*.from' => ['required', 'string'],
            'transitions.*.to' => ['required', 'string'],
            'transitions.*.guard' => ['sometimes', 'string'],
            'initial_state' => ['required', 'string'],
        ];

        $validator = Validator::make($definition, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate that initial_state exists in states
        if (!in_array($definition['initial_state'], $definition['states'])) {
            throw ValidationException::withMessages([
                'definition.initial_state' => ['Initial state must be one of the defined states.'],
            ]);
        }

        // Validate that transition from/to states exist
        foreach ($definition['transitions'] as $index => $transition) {
            if (!in_array($transition['from'], $definition['states'])) {
                throw ValidationException::withMessages([
                    "definition.transitions.{$index}.from" => ['Transition "from" state must be one of the defined states.'],
                ]);
            }

            if (!in_array($transition['to'], $definition['states'])) {
                throw ValidationException::withMessages([
                    "definition.transitions.{$index}.to" => ['Transition "to" state must be one of the defined states.'],
                ]);
            }
        }

        // Check for duplicate transition names
        $transitionNames = array_column($definition['transitions'], 'name');
        if (count($transitionNames) !== count(array_unique($transitionNames))) {
            throw ValidationException::withMessages([
                'definition.transitions' => ['Duplicate transition names are not allowed.'],
            ]);
        }
    }

    /**
     * Clone a workflow with a new key.
     * 
     * @param string $sourceKey Source workflow key
     * @param string $newKey New workflow key
     * @param string|null $newName Optional new name
     * @param string|null $createdBy User ID creating the clone
     * @return WorkflowDefinition
     * @throws ValidationException
     */
    public function clone(string $sourceKey, string $newKey, ?string $newName = null, ?string $createdBy = null): WorkflowDefinition
    {
        $source = WorkflowDefinition::getLatestVersion($sourceKey);

        if (!$source) {
            throw ValidationException::withMessages([
                'key' => ['Source workflow not found.'],
            ]);
        }

        if (WorkflowDefinition::where('key', $newKey)->exists()) {
            throw ValidationException::withMessages([
                'key' => ['A workflow with the new key already exists.'],
            ]);
        }

        return DB::transaction(function () use ($source, $newKey, $newName, $createdBy) {
            return WorkflowDefinition::create([
                'name' => $newName ?? $source->name . ' (Copy)',
                'key' => $newKey,
                'description' => $source->description,
                'version' => 1,
                'definition' => $source->definition,
                'is_active' => false,
                'created_by' => $createdBy,
            ]);
        });
    }
}
