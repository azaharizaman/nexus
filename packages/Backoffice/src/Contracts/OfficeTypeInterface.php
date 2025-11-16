<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Office Type Interface
 * 
 * Defines the data structure contract for an Office Type entity.
 * Represents a classification or category of office (e.g., headquarters, branch, regional).
 */
interface OfficeTypeInterface
{
    /**
     * Get the office type's unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the office type's name.
     */
    public function getName(): string;

    /**
     * Get the office type's code.
     */
    public function getCode(): ?string;

    /**
     * Get the office type's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the office type's status.
     */
    public function getStatus(): string;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;
}
