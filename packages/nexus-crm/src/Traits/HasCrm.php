<?php

declare(strict_types=1);

namespace Nexus\Crm\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nexus\Crm\Core\CrmManager;
use Nexus\Crm\Events\ContactCreatedEvent;
use Nexus\Crm\Events\ContactUpdatedEvent;
use Nexus\Crm\Events\ContactDeletedEvent;

/**
 * HasCrm Trait
 *
 * Adds CRM functionality to any Eloquent model without requiring database migrations.
 * Stores CRM data as JSON in a designated column on the host model.
 *
 * @property array $crmConfiguration CRM configuration array
 */
trait HasCrm
{
    /**
     * Boot the HasCrm trait.
     */
    public static function bootHasCrm(): void
    {
        // Trait boot method - intentionally empty
        // Any initialization logic can be added here if needed
    }

    /**
     * Get the CRM manager instance for this model.
     */
    public function crm(): CrmManager
    {
        return new CrmManager($this);
    }

    /**
     * Get the CRM configuration for this model.
     */
    public function getCrmConfiguration(): array
    {
        // For Level 1, prioritize model property over config
        if (property_exists($this, 'crmConfiguration') && !empty($this->crmConfiguration)) {
            return $this->crmConfiguration;
        }

        // For Level 2+, try to get from config with fallback
        try {
            return config('crm.defaults.contacts.fields', [
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true],
                'email' => ['type' => 'string', 'required' => false],
                'phone' => ['type' => 'string', 'required' => false],
                'company' => ['type' => 'string', 'required' => false],
                'notes' => ['type' => 'text', 'required' => false],
            ]);
        } catch (\Throwable $e) {
            // In test environments or when config is not available, use defaults
            return [
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true],
                'email' => ['type' => 'string', 'required' => false],
                'phone' => ['type' => 'string', 'required' => false],
                'company' => ['type' => 'string', 'required' => false],
                'notes' => ['type' => 'text', 'required' => false],
            ];
        }
    }

    /**
     * Get CRM data stored on this model.
     */
    public function getCrmData(): array
    {
        $crmColumn = $this->getCrmDataColumn();

        // For Level 1 (trait-based), check if we should persist data
        if (!$this->shouldPersistCrmData()) {
            // Use attributes array directly to avoid database connection issues
            return $this->attributes[$crmColumn] ?? [];
        }

        // Handle both cast and non-cast attributes for Level 2+
        if (isset($this->casts[$crmColumn]) && $this->casts[$crmColumn] === 'array') {
            return $this->$crmColumn ?? [];
        }

        $data = $this->$crmColumn ?? '[]';
        return is_array($data) ? $data : json_decode($data, true) ?? [];
    }

    /**
     * Set CRM data on this model.
     */
    public function setCrmData(array $data): self
    {
        $crmColumn = $this->getCrmDataColumn();

        // For Level 1 (trait-based), set in attributes array directly
        if (!$this->shouldPersistCrmData()) {
            $this->attributes[$crmColumn] = $data;
            return $this;
        }

        $this->$crmColumn = $data;
        return $this;
    }

    /**
     * Get the column name used to store CRM data.
     */
    protected function getCrmDataColumn(): string
    {
        return 'crm_data';
    }

    /**
     * Get contacts associated with this model.
     */
    public function getContacts(): Collection
    {
        $data = $this->getCrmData();

        return collect($data['contacts'] ?? []);
    }

    /**
     * Add a contact to this model.
     */
    public function addContact(array $contactData): self
    {
        // Validate the contact data
        $this->validateContactData($contactData);

        // Check permissions
        if (!$this->crm()->can('create_contact')) {
            throw new \RuntimeException('Insufficient permissions to create contact');
        }

        // Get existing contacts
        $data = $this->getCrmData();
        $contacts = $data['contacts'] ?? [];

        // Add the new contact with ID and timestamps
        $now = $this->shouldPersistCrmData() ? now()->toISOString() : date('c');
        $contact = array_merge($contactData, [
            'id' => uniqid('contact_', true),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $contacts[] = $contact;
        $data['contacts'] = $contacts;

        // Save the data (only for Level 2+)
        $this->setCrmData($data);
        if ($this->shouldPersistCrmData()) {
            $this->save();
        }

        // Fire event (only if event system is available)
        try {
            ContactCreatedEvent::dispatch($this, $contact);
        } catch (\Throwable $e) {
            // In test environments or when event system is not available, skip dispatching
        }

        return $this;
    }

    /**
     * Update a contact.
     */
    public function updateContact(string $contactId, array $contactData): self
    {
        // Check permissions
        if (!$this->crm()->can('update_contact')) {
            throw new \RuntimeException('Insufficient permissions to update contact');
        }

        $data = $this->getCrmData();
        $contacts = $data['contacts'] ?? [];

        foreach ($contacts as &$contact) {
            if (($contact['id'] ?? null) === $contactId) {
                // Validate the updated data
                $this->validateContactData(array_merge($contact, $contactData));

                $now = $this->shouldPersistCrmData() ? now()->toISOString() : date('c');
                $contact = array_merge($contact, $contactData, [
                    'updated_at' => $now,
                ]);

                $data['contacts'] = $contacts;
                $this->setCrmData($data);

                // Save only for Level 2+
                if ($this->shouldPersistCrmData()) {
                    $this->save();
                }

                // Fire event (only if event system is available)
                try {
                    ContactUpdatedEvent::dispatch($this, $contact);
                } catch (\Throwable $e) {
                    // In test environments or when event system is not available, skip dispatching
                }

                return $this;
            }
        }

        throw new \InvalidArgumentException("Contact with ID {$contactId} not found");
    }

    /**
     * Delete a contact.
     */
    public function deleteContact(string $contactId): self
    {
        // Check permissions
        if (!$this->crm()->can('delete_contact')) {
            throw new \RuntimeException('Insufficient permissions to delete contact');
        }

        $data = $this->getCrmData();
        $contacts = $data['contacts'] ?? [];

        $filteredContacts = array_filter($contacts, function ($contact) use ($contactId) {
            return ($contact['id'] ?? null) !== $contactId;
        });

        if (count($filteredContacts) === count($contacts)) {
            throw new \InvalidArgumentException("Contact with ID {$contactId} not found");
        }

        $deletedContact = array_filter($contacts, function ($contact) use ($contactId) {
            return ($contact['id'] ?? null) === $contactId;
        });

        $data['contacts'] = array_values($filteredContacts);
        $this->setCrmData($data);

        // Save only for Level 2+
        if ($this->shouldPersistCrmData()) {
            $this->save();
        }

        // Fire event (only if event system is available)
        try {
            ContactDeletedEvent::dispatch($this, reset($deletedContact));
        } catch (\Throwable $e) {
            // In test environments or when event system is not available, skip dispatching
        }

        return $this;
    }

    /**
     * Validate contact data against the CRM configuration.
     */
    protected function validateContactData(array $data): void
    {
        $configuration = $this->getCrmConfiguration();

        foreach ($configuration as $field => $rules) {
            if (($rules['required'] ?? false) && !isset($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }

            // Type validation
            $expectedType = $rules['type'] ?? 'string';
            $actualValue = $data[$field] ?? null;

            if ($actualValue !== null) {
                $this->validateFieldType($field, $actualValue, $expectedType);
            }
        }
    }

    /**
     * Validate a field value against its expected type.
     */
    protected function validateFieldType(string $field, mixed $value, string $expectedType): void
    {
        $actualType = gettype($value);

        // Basic type validation
        switch ($expectedType) {
            case 'string':
                if (!is_string($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be a string, got {$actualType}");
                }
                break;
            case 'int':
            case 'integer':
                if (!is_int($value) && !is_numeric($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be an integer, got {$actualType}");
                }
                break;
            case 'float':
            case 'double':
                if (!is_float($value) && !is_numeric($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be a float, got {$actualType}");
                }
                break;
            case 'bool':
            case 'boolean':
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be a boolean, got {$actualType}");
                }
                break;
            case 'array':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be an array, got {$actualType}");
                }
                break;
            case 'text':
                // Text is just a longer string, no specific validation
                if (!is_string($value)) {
                    throw new \InvalidArgumentException("Field '{$field}' must be a string, got {$actualType}");
                }
                break;
            default:
                // Unknown type, allow anything
                break;
        }
    }

    /**
     * Determine if CRM data should be persisted to the database.
     */
    protected function shouldPersistCrmData(): bool
    {
        // For Level 1 (trait-based), we don't persist to database
        // Check if the model has any indication it's Level 2+
        if (property_exists($this, 'crmLevel') && $this->crmLevel >= 2) {
            try {
                return config('crm.level', 1) >= 2;
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false; // Default to Level 1
    }
}