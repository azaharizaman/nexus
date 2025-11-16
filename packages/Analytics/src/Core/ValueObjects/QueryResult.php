<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\ValueObjects;

final class QueryResult
{
    public function __construct(public readonly array $rows = []) {}
}
