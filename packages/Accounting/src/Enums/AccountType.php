<?php

declare(strict_types=1);

namespace Nexus\Accounting\Enums;

final class AccountType
{
    public const ASSET = 'Asset';
    public const LIABILITY = 'Liability';
    public const EQUITY = 'Equity';
    public const REVENUE = 'Revenue';
    public const EXPENSE = 'Expense';

    public static function all(): array
    {
        return [self::ASSET, self::LIABILITY, self::EQUITY, self::REVENUE, self::EXPENSE];
    }
}
