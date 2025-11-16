<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Contracts;

/**
 * Audit Log Data Structure Contract
 *
 * Defines the data structure for audit log entries.
 * This interface describes what an audit log IS (data structure),
 * not how it's stored or retrieved (that's the repository's job).
 */
interface AuditLogInterface
{
    /**
     * Get the unique identifier of the audit log
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Get the log name/category
     *
     * @return string
     */
    public function getLogName(): string;

    /**
     * Get the human-readable description of the activity
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the subject (entity) type that was acted upon
     *
     * @return string|null
     */
    public function getSubjectType(): ?string;

    /**
     * Get the subject (entity) ID that was acted upon
     *
     * @return int|null
     */
    public function getSubjectId(): ?int;

    /**
     * Get the event type (created, updated, deleted, etc.)
     *
     * @return string|null
     */
    public function getEvent(): ?string;

    /**
     * Get the causer (user/system) type
     *
     * @return string|null
     */
    public function getCauserType(): ?string;

    /**
     * Get the causer (user/system) ID
     *
     * @return int|null
     */
    public function getCauserId(): ?int;

    /**
     * Get the properties/metadata associated with the log
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array;

    /**
     * Get a specific property value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getProperty(string $key, mixed $default = null): mixed;

    /**
     * Get the batch UUID (for grouping related activities)
     *
     * @return string|null
     */
    public function getBatchUuid(): ?string;

    /**
     * Get the tenant ID
     *
     * @return string|null
     */
    public function getTenantId(): ?string;

    /**
     * Get the IP address from which the activity originated
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Get the user agent string
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Get the audit level (1=Low, 2=Medium, 3=High, 4=Critical)
     *
     * @return int
     */
    public function getAuditLevel(): int;

    /**
     * Get the retention period in days
     *
     * @return int
     */
    public function getRetentionDays(): int;

    /**
     * Get the timestamp when the log was created
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the timestamp when the log was last updated
     *
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Check if this log entry has expired based on retention policy
     *
     * @return bool
     */
    public function hasExpired(): bool;

    /**
     * Get the formatted description with placeholders replaced
     *
     * @return string
     */
    public function getFormattedDescription(): string;

    /**
     * Get the audit level as a human-readable string
     *
     * @return string
     */
    public function getAuditLevelName(): string;
}
