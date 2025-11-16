<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Unit Group Interface
 * 
 * Defines the data structure contract for a Unit Group entity.
 * Represents a grouping of related units in the organization.
 */
interface UnitGroupInterface
{
    /**
     * Get the unit group's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the unit group's name.
     */
    public function getName(): string;

    /**
     * Get the unit group's code.
     */
    public function getCode(): ?string;

    /**
     * Get the unit group's description.
     */
    public function getDescription(): ?string;

    /**
     * Check if the unit group is active.
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
