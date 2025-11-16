<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Type Data Structure Contract
 *
 * Defines the data structure for a unit type/category (e.g., mass, length, volume).
 */
interface UomTypeInterface
{
    /**
     * Get the unique identifier of the type
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Get the type code (e.g., 'mass', 'length', 'volume')
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the human-readable name of the type
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the type
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get the base unit code for this type
     *
     * @return string|null
     */
    public function getBaseUnit(): ?string;

    /**
     * Check if this type is active
     *
     * @return bool
     */
    public function isActive(): bool;
}
