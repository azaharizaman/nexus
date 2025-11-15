<?php

declare(strict_types=1);

namespace Nexus\Hrm\Enums;

enum LeaveStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case TAKEN = 'taken';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::TAKEN => 'Taken',
        };
    }

    /**
     * Check if leave is active (approved or taken)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::APPROVED, self::TAKEN]);
    }
}
