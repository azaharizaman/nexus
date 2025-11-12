<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Exceptions;

use Exception;

/**
 * Sequence Not Found Exception
 *
 * Thrown when a requested sequence does not exist.
 */
class SequenceNotFoundException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return static
     */
    public static function create(string $tenantId, string $sequenceName): static
    {
        return new static("Sequence '{$sequenceName}' not found for tenant '{$tenantId}'");
    }
}
