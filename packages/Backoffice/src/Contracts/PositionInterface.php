<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Position Interface
 * 
 * Defines the data structure contract for a Position entity.
 * Represents a job position or role in the organization.
 */
interface PositionInterface
{
    /**
     * Get the position's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the position's title.
     */
    public function getTitle(): string;

    /**
     * Get the position's code.
     */
    public function getCode(): ?string;

    /**
     * Get the position's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the position type.
     */
    public function getType(): ?string;

    /**
     * Get the position level.
     */
    public function getLevel(): ?int;

    /**
     * Check if the position is active.
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
