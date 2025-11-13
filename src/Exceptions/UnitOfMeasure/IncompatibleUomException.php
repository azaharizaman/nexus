<?php

declare(strict_types=1);

namespace Nexus\Erp\Exceptions\UnitOfMeasure;

/**
 * Incompatible UOM Exception
 *
 * Thrown when attempting to convert between units from different categories
 * (e.g., mass to length, volume to area).
 */
class IncompatibleUomException extends UomConversionException
{
    /**
     * HTTP status code for unprocessable entity
     */
    protected int $httpStatusCode = 422;

    /**
     * Source UOM category
     */
    protected string $fromCategory;

    /**
     * Target UOM category
     */
    protected string $toCategory;

    /**
     * Create a new incompatible UOM exception
     *
     * @param  string  $fromCategory  Source category name
     * @param  string  $toCategory  Target category name
     * @param  int  $code  Error code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $fromCategory,
        string $toCategory,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->fromCategory = $fromCategory;
        $this->toCategory = $toCategory;

        $message = sprintf(
            'Cannot convert between incompatible categories: %s and %s',
            $fromCategory,
            $toCategory
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get source category
     */
    public function getFromCategory(): string
    {
        return $this->fromCategory;
    }

    /**
     * Get target category
     */
    public function getToCategory(): string
    {
        return $this->toCategory;
    }
}
