<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Http\Controllers;

use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsServiceContract;
use Azaharizaman\Erp\SettingsManagement\Http\Requests\BulkUpdateSettingsRequest;
use Azaharizaman\Erp\SettingsManagement\Http\Requests\CreateSettingRequest;
use Azaharizaman\Erp\SettingsManagement\Http\Requests\ImportSettingsRequest;
use Azaharizaman\Erp\SettingsManagement\Http\Requests\UpdateSettingRequest;
use Azaharizaman\Erp\SettingsManagement\Http\Resources\SettingResource;
use Azaharizaman\Erp\SettingsManagement\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Settings Controller
 *
 * Handles API requests for settings management.
 */
class SettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param SettingsServiceContract $settingsService
     */
    public function __construct(
        private readonly SettingsServiceContract $settingsService
    ) {}

    /**
     * Display a listing of settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $scope = $request->input('scope', 'tenant');
        $moduleName = $request->input('module_name');
        $userId = $request->input('user_id');
        $tenantId = $request->user()->tenant_id;

        // Get all settings for scope
        $settings = $this->settingsService->all($scope, $tenantId, $moduleName, $userId);

        // Convert to Setting models for resource transformation
        $settingModels = collect($settings)->map(function ($value, $key) use ($scope, $tenantId, $moduleName, $userId) {
            return (object) [
                'key' => $key,
                'value' => $value,
                'scope' => $scope,
                'tenant_id' => $tenantId,
                'module_name' => $moduleName,
                'user_id' => $userId,
            ];
        })->values();

        return response()->json([
            'data' => $settingModels,
            'meta' => [
                'count' => count($settings),
                'scope' => $scope,
            ],
        ]);
    }

    /**
     * Store a newly created setting.
     *
     * @param CreateSettingRequest $request
     * @return JsonResponse
     */
    public function store(CreateSettingRequest $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $validated = $request->validated();

        // Check scope-specific authorization
        $this->checkScopeAuthorization($validated['scope'], $request->user());

        $success = $this->settingsService->set(
            key: $validated['key'],
            value: $validated['value'],
            type: $validated['type'],
            scope: $validated['scope'],
            metadata: $validated['metadata'] ?? [],
            tenantId: $validated['scope'] === 'system' ? null : $request->user()->tenant_id,
            moduleName: $validated['module_name'] ?? null,
            userId: $validated['user_id'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => 'Failed to create setting',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Retrieve the created setting
        $setting = Setting::where('key', $validated['key'])
            ->where('scope', $validated['scope'])
            ->first();

        return SettingResource::make($setting)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified setting.
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $scope = $request->input('scope');
        $moduleName = $request->input('module_name');
        $userId = $request->input('user_id');
        $tenantId = $request->user()->tenant_id;

        $value = $this->settingsService->get($key, null, $scope, $tenantId, $moduleName, $userId);

        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Find the actual setting model for authorization
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            $this->authorize('view', $setting);
        }

        return response()->json([
            'data' => [
                'key' => $key,
                'value' => $value,
            ],
        ]);
    }

    /**
     * Update the specified setting.
     *
     * @param UpdateSettingRequest $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        $validated = $request->validated();
        $scope = $request->input('scope', 'tenant');
        $tenantId = $request->user()->tenant_id;

        // Find the setting
        $setting = Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $setting);

        $success = $this->settingsService->set(
            key: $key,
            value: $validated['value'] ?? $setting->value,
            type: $validated['type'] ?? $setting->type,
            scope: $scope,
            metadata: $validated['metadata'] ?? $setting->metadata ?? [],
            tenantId: $tenantId
        );

        if (!$success) {
            return response()->json([
                'message' => 'Failed to update setting',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $setting->refresh();

        return SettingResource::make($setting)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified setting.
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        $scope = $request->input('scope', 'tenant');
        $tenantId = $request->user()->tenant_id;

        $setting = Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('delete', $setting);

        $success = $this->settingsService->forget($key, $scope, $tenantId);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to delete setting',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk update multiple settings.
     *
     * @param BulkUpdateSettingsRequest $request
     * @return JsonResponse
     */
    public function bulk(BulkUpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $validated = $request->validated();
        $scope = $request->input('scope', 'tenant');
        $tenantId = $request->user()->tenant_id;

        $this->checkScopeAuthorization($scope, $request->user());

        DB::beginTransaction();
        try {
            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($validated['settings'] as $settingData) {
                try {
                    $success = $this->settingsService->set(
                        key: $settingData['key'],
                        value: $settingData['value'],
                        type: $settingData['type'] ?? 'string',
                        scope: $scope,
                        metadata: $settingData['metadata'] ?? [],
                        tenantId: $scope === 'system' ? null : $tenantId
                    );

                    if ($success) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to update setting: {$settingData['key']}";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Error updating {$settingData['key']}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Bulk update completed',
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'total' => count($validated['settings']),
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk update failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export settings to JSON or CSV.
     *
     * @param Request $request
     * @return Response
     */
    public function export(Request $request): Response
    {
        Gate::authorize('export-settings');

        $scope = $request->input('scope', 'tenant');
        $format = $request->input('format', 'json');
        $tenantId = $request->user()->tenant_id;

        $settings = $this->settingsService->all($scope, $tenantId);

        if ($format === 'csv') {
            return $this->exportCsv($settings, $scope);
        }

        return $this->exportJson($settings, $scope);
    }

    /**
     * Import settings from JSON or CSV file.
     *
     * @param ImportSettingsRequest $request
     * @return JsonResponse
     */
    public function import(ImportSettingsRequest $request): JsonResponse
    {
        Gate::authorize('import-settings');

        $validated = $request->validated();
        $file = $request->file('file');
        $scope = $validated['scope'] ?? 'tenant';
        $overwrite = $validated['overwrite'] ?? false;
        $tenantId = $request->user()->tenant_id;

        $this->checkScopeAuthorization($scope, $request->user());

        try {
            $content = file_get_contents($file->getRealPath());
            $settings = json_decode($content, true);

            if (!is_array($settings)) {
                return response()->json([
                    'message' => 'Invalid file format',
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();
            
            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($settings as $key => $value) {
                try {
                    // Check if setting exists and skip if not overwriting
                    if (!$overwrite && $this->settingsService->has($key, $scope, $tenantId)) {
                        $skippedCount++;
                        continue;
                    }

                    $type = is_bool($value) ? 'boolean' :
                            (is_int($value) ? 'integer' :
                            (is_array($value) ? 'array' : 'string'));

                    $this->settingsService->set($key, $value, $type, $scope, [], $tenantId);
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to import {$key}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Import completed',
                'data' => [
                    'imported' => $importedCount,
                    'skipped' => $skippedCount,
                    'total' => count($settings),
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export settings as JSON
     *
     * @param array<string, mixed> $settings
     * @param string $scope
     * @return Response
     */
    protected function exportJson(array $settings, string $scope): Response
    {
        $json = json_encode($settings, JSON_PRETTY_PRINT);
        $filename = "settings-{$scope}-" . date('Y-m-d-His') . '.json';

        return response($json, Response::HTTP_OK, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export settings as CSV
     *
     * @param array<string, mixed> $settings
     * @param string $scope
     * @return Response
     */
    protected function exportCsv(array $settings, string $scope): Response
    {
        $csv = "Key,Value\n";
        foreach ($settings as $key => $value) {
            $valueStr = is_array($value) ? json_encode($value) : (string) $value;
            $csv .= "\"{$key}\",\"{$valueStr}\"\n";
        }

        $filename = "settings-{$scope}-" . date('Y-m-d-His') . '.csv';

        return response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Check scope-specific authorization
     *
     * @param string $scope
     * @param mixed $user
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function checkScopeAuthorization(string $scope, mixed $user): void
    {
        if ($scope === 'system' && !$user->hasRole('super-admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Only super admins can manage system settings');
        }
    }
}
