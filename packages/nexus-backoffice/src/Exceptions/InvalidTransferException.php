<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use InvalidArgumentException;

/**
 * Invalid Transfer Exception
 * 
 * Thrown when a staff transfer operation is invalid or cannot be completed.
 * 
 * @package Nexus\Backoffice\Exceptions
 */
class InvalidTransferException extends InvalidArgumentException
{
    /**
     * Create exception for same office transfer
     */
    public static function sameOffice(): self
    {
        return new self('Cannot transfer staff to the same office');
    }
    
    /**
     * Create exception for pending transfer exists
     */
    public static function pendingTransferExists(): self
    {
        return new self('Staff already has a pending or approved transfer');
    }
    
    /**
     * Create exception for past effective date
     */
    public static function pastEffectiveDate(): self
    {
        return new self('Effective date cannot be in the past for scheduled transfers');
    }
    
    /**
     * Create exception for circular supervisor reference
     */
    public static function circularSupervisorReference(): self
    {
        return new self('Cannot assign supervisor who reports to this staff member');
    }
    
    /**
     * Create exception for self supervision
     */
    public static function selfSupervision(): self
    {
        return new self('Staff cannot be their own supervisor');
    }
    
    /**
     * Create exception for invalid status transition
     */
    public static function invalidStatusTransition(string $currentStatus, string $newStatus): self
    {
        return new self("Cannot change transfer status from {$currentStatus} to {$newStatus}");
    }
    
    /**
     * Create exception for transfer not due for processing
     */
    public static function notDueForProcessing(): self
    {
        return new self('Transfer is not due for processing yet');
    }
}