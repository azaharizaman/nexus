<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Department Interface
 * 
 * Defines the data structure contract for a Department entity.
 * Represents a department within an office or company structure.
 */
interface DepartmentInterface
{
    /**
     * Get the department's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the department's name.
     */
    public function getName(): string;

    /**
     * Get the department's code.
     */
    public function getCode(): ?string;

    /**
     * Get the department's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the office ID this department belongs to.
     */
    public function getOfficeId(): int;

    /**
     * Get the parent department ID.
     */
    public function getParentDepartmentId(): ?int;

    /**
     * Check if the department is active.
     */
    public function isActive(): bool;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Get the deletion timestamp (soft delete).
     */
    public function getDeletedAt(): ?\DateTimeInterface;
}
