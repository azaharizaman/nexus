<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Exceptions;

use Exception;

/**
 * Duplicate Number Exception
 *
 * Thrown when attempting to generate or override a serial number
 * that already exists.
 */
class DuplicateNumberException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $number  The duplicate number
     * @param  string  $sequenceName  The sequence name
     * @return static
     */
    public static function create(string $number, string $sequenceName): static
    {
        return new static("Serial number '{$number}' already exists in sequence '{$sequenceName}'");
    }
}
