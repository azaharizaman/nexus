<?php

declare(strict_types=1);

namespace Nexus\Hrm\Enums;

enum EmploymentStatus: string
{
    case PROSPECT = 'prospect';
    case ACTIVE = 'active';
    case PROBATION = 'probation';
    case PERMANENT = 'permanent';
    case NOTICE = 'notice';
    case TERMINATED = 'terminated';
    case RESIGNED = 'resigned';
    case RETIRED = 'retired';
    case SUSPENDED = 'suspended';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PROSPECT => 'Prospect',
            self::ACTIVE => 'Active',
            self::PROBATION => 'On Probation',
            self::PERMANENT => 'Permanent',
            self::NOTICE => 'Notice Period',
            self::TERMINATED => 'Terminated',
            self::RESIGNED => 'Resigned',
            self::RETIRED => 'Retired',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Check if employee is currently employed
     */
    public function isEmployed(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::PROBATION,
            self::PERMANENT,
            self::NOTICE,
        ]);
    }

    /**
     * Check if employee can have leave entitlements
     */
    public function canHaveLeaveEntitlements(): bool
    {
        return in_array($this, [
            self::PERMANENT,
            self::ACTIVE,
        ]);
    }
}
