<?php

declare(strict_types=1);

namespace Nexus\Erp\Exceptions\UnitOfMeasure;

/**
 * Invalid Quantity Exception
 *
 * Thrown when a quantity value is invalid for conversion
 * (e.g., negative numbers, non-numeric values, NaN, infinity).
 */
class InvalidQuantityException extends UomConversionException
{
    /**
     * HTTP status code for unprocessable entity
     */
    protected int $httpStatusCode = 422;

    /**
     * Invalid quantity value
     */
    protected string $quantity;

    /**
     * Reason for invalidity
     */
    protected string $reason;

    /**
     * Create a new invalid quantity exception
     *
     * @param  string  $quantity  Invalid quantity value
     * @param  string  $reason  Reason for invalidity
     * @param  int  $code  Error code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $quantity,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->quantity = $quantity;
        $this->reason = $reason;

        $message = sprintf("Invalid quantity '%s': %s", $quantity, $reason);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the invalid quantity
     */
    public function getQuantity(): string
    {
        return $this->quantity;
    }

    /**
     * Get the reason for invalidity
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
