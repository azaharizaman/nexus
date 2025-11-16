<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Services;

use Nexus\AuditLog\Contracts\AuditLogInterface;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Exceptions\AuditLogException;

/**
 * Audit Log Manager Service
 *
 * Core business logic for audit logging.
 * This service is framework-agnostic and contains no Laravel dependencies.
 */
class AuditLogManager
{
    public function __construct(
        private readonly AuditLogRepositoryContract $repository
    ) {
    }

    /**
     * Create a new audit log entry
     *
     * @param array<string, mixed> $data
     * @return AuditLogInterface
     * @throws AuditLogException
     */
    public function log(array $data): AuditLogInterface
    {
        $this->validateLogData($data);

        return $this->repository->create($data);
    }

    /**
     * Find an audit log by ID
     *
     * @param int $id
     * @return AuditLogInterface|null
     */
    public function findById(int $id): ?AuditLogInterface
    {
        return $this->repository->find($id);
    }

    /**
     * Search audit logs with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return array{data: array<int, AuditLogInterface>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function search(array $filters, int $perPage = 50): array
    {
        if ($perPage > 1000) {
            $perPage = 1000; // Maximum limit
        }

        return $this->repository->search($filters, $perPage);
    }

    /**
     * Get audit logs for a specific subject
     *
     * @param string $subjectType
     * @param int $subjectId
     * @param int $limit
     * @return array<int, AuditLogInterface>
     */
    public function getForSubject(string $subjectType, int $subjectId, int $limit = 100): array
    {
        return $this->repository->getForSubject($subjectType, $subjectId, $limit);
    }

    /**
     * Get audit logs by causer
     *
     * @param string $causerType
     * @param int $causerId
     * @param int $limit
     * @return array<int, AuditLogInterface>
     */
    public function getByCauser(string $causerType, int $causerId, int $limit = 100): array
    {
        return $this->repository->getByCauser($causerType, $causerId, $limit);
    }

    /**
     * Get audit logs within a date range
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param string|null $tenantId
     * @return array<int, AuditLogInterface>
     */
    public function getByDateRange(\DateTimeInterface $from, \DateTimeInterface $to, ?string $tenantId = null): array
    {
        return $this->repository->getByDateRange($from, $to, $tenantId);
    }

    /**
     * Purge expired audit logs
     *
     * @param \DateTimeInterface $before
     * @param string|null $tenantId
     * @return int Number of logs purged
     */
    public function purgeExpired(\DateTimeInterface $before, ?string $tenantId = null): int
    {
        return $this->repository->purgeExpired($before, $tenantId);
    }

    /**
     * Get activity statistics
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->repository->getStatistics($filters);
    }

    /**
     * Export audit logs
     *
     * @param array<string, mixed> $filters
     * @param int $maxRecords
     * @return array<int, AuditLogInterface>
     */
    public function export(array $filters, int $maxRecords = 10000): array
    {
        return $this->repository->export($filters, $maxRecords);
    }

    /**
     * Validate log data before creating
     *
     * @param array<string, mixed> $data
     * @return void
     * @throws AuditLogException
     */
    protected function validateLogData(array $data): void
    {
        if (empty($data['log_name'])) {
            throw new AuditLogException('Log name is required');
        }

        if (empty($data['description'])) {
            throw new AuditLogException('Description is required');
        }

        if (isset($data['audit_level']) && !in_array($data['audit_level'], [1, 2, 3, 4], true)) {
            throw new AuditLogException('Audit level must be 1 (Low), 2 (Medium), 3 (High), or 4 (Critical)');
        }

        if (isset($data['retention_days']) && $data['retention_days'] < 0) {
            throw new AuditLogException('Retention days cannot be negative');
        }
    }

    /**
     * Mask sensitive fields in data
     *
     * @param array<string, mixed> $data
     * @param array<string> $sensitiveFields
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data, array $sensitiveFields): array
    {
        return $this->maskRecursive($data, $sensitiveFields);
    }

    /**
     * Recursively mask sensitive fields
     *
     * @param mixed $data
     * @param array<string> $sensitiveFields
     * @return mixed
     */
    protected function maskRecursive(mixed $data, array $sensitiveFields): mixed
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $key => $value) {
                $shouldMask = false;
                foreach ($sensitiveFields as $field) {
                    if (str_contains(strtolower((string) $key), strtolower($field))) {
                        $shouldMask = true;
                        break;
                    }
                }

                if ($shouldMask) {
                    $masked[$key] = '[REDACTED]';
                } else {
                    $masked[$key] = $this->maskRecursive($value, $sensitiveFields);
                }
            }

            return $masked;
        }

        if (is_object($data)) {
            return '[OBJECT]';
        }

        return $data;
    }
}
