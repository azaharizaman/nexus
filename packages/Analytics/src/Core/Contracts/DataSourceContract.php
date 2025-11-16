<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Contracts;

interface DataSourceContract
{
    /**
     * Fetch data for the specified query parameters.
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $params): array;
}
