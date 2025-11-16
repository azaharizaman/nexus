<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\ValueObjects;

final class QueryDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly array $definition
    ) {}
}
