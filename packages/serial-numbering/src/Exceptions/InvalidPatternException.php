<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Exceptions;

use Exception;

/**
 * Invalid Pattern Exception
 *
 * Thrown when a serial number pattern is invalid or contains
 * unrecognized variables.
 */
class InvalidPatternException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $pattern  The invalid pattern
     * @param  string  $reason  The reason why it's invalid
     * @return static
     */
    public static function create(string $pattern, string $reason): static
    {
        return new static("Invalid pattern '{$pattern}': {$reason}");
    }
}
