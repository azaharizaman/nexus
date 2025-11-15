<?php

declare(strict_types=1);

namespace Nexus\Hrm\Enums;

enum ContractType: string
{
    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case TEMPORARY = 'temporary';
    case INTERN = 'intern';
    case CONSULTANT = 'consultant';
    case PROBATION = 'probation';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PERMANENT => 'Permanent Employment',
            self::CONTRACT => 'Fixed-term Contract',
            self::TEMPORARY => 'Temporary Employment',
            self::INTERN => 'Internship',
            self::CONSULTANT => 'Consultant',
            self::PROBATION => 'Probationary Period',
        };
    }

    /**
     * Check if contract type has fixed end date
     */
    public function hasEndDate(): bool
    {
        return in_array($this, [
            self::CONTRACT,
            self::TEMPORARY,
            self::INTERN,
            self::PROBATION,
        ]);
    }
}
