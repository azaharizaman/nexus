<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Unit Interface
 * 
 * Defines the data structure contract for a Unit entity.
 * Represents a functional unit or team within the organization.
 */
interface UnitInterface
{
    /**
     * Get the unit's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the unit's name.
     */
    public function getName(): string;

    /**
     * Get the unit's code.
     */
    public function getCode(): ?string;

    /**
     * Get the unit's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the unit group ID this unit belongs to.
     */
    public function getUnitGroupId(): ?int;

    /**
     * Get the parent unit ID.
     */
    public function getParentUnitId(): ?int;

    /**
     * Check if the unit is active.
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
