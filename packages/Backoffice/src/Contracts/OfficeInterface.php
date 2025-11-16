<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Office Interface
 * 
 * Defines the data structure contract for an Office entity.
 * Represents a physical or virtual office location within a company.
 */
interface OfficeInterface
{
    /**
     * Get the office's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the office's name.
     */
    public function getName(): string;

    /**
     * Get the office's code.
     */
    public function getCode(): ?string;

    /**
     * Get the office's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the company ID this office belongs to.
     */
    public function getCompanyId(): int;

    /**
     * Get the parent office ID.
     */
    public function getParentOfficeId(): ?int;

    /**
     * Check if the office is active.
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
