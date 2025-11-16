<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Company Interface
 * 
 * Defines the data structure contract for a Company entity.
 * Represents a company that can have parent-child relationships in a hierarchy.
 */
interface CompanyInterface
{
    /**
     * Get the company's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the company's name.
     */
    public function getName(): string;

    /**
     * Get the company's code.
     */
    public function getCode(): ?string;

    /**
     * Get the company's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the parent company ID.
     */
    public function getParentCompanyId(): ?int;

    /**
     * Check if the company is active.
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
