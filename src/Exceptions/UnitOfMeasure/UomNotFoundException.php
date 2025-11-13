<?php

declare(strict_types=1);

namespace Nexus\Erp\Exceptions\UnitOfMeasure;

/**
 * UOM Not Found Exception
 *
 * Thrown when a requested unit of measure code does not exist
 * in the database (neither system nor tenant-specific).
 */
class UomNotFoundException extends UomConversionException
{
    /**
     * HTTP status code for not found
     */
    protected int $httpStatusCode = 404;

    /**
     * UOM code that was not found
     */
    protected string $uomCode;

    /**
     * Create a new UOM not found exception
     *
     * @param  string  $code  UOM code that was not found
     * @param  int  $errorCode  Error code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(string $code, int $errorCode = 0, ?\Throwable $previous = null)
    {
        $this->uomCode = $code;

        $message = sprintf('Unit of measure not found: %s', $code);

        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Get the UOM code that was not found
     */
    public function getUomCode(): string
    {
        return $this->uomCode;
    }
}
