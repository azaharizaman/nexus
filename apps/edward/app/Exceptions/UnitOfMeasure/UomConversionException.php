<?php

declare(strict_types=1);

namespace Nexus\Erp\Exceptions\UnitOfMeasure;

use RuntimeException;

/**
 * Base UOM Conversion Exception
 *
 * Thrown when a unit of measure conversion operation fails.
 * This serves as the base exception for all UOM conversion-related errors.
 */
class UomConversionException extends RuntimeException
{
    /**
     * Default HTTP status code for conversion errors
     */
    protected int $httpStatusCode = 400;

    /**
     * Create a new UOM conversion exception
     *
     * @param  string  $message  Error message
     * @param  int  $code  Error code (default: 0)
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(string $message = 'UOM conversion failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get HTTP status code for this exception
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Set HTTP status code
     *
     * @return $this
     */
    public function setHttpStatusCode(int $code): self
    {
        $this->httpStatusCode = $code;

        return $this;
    }
}
