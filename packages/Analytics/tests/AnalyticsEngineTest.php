<?php

declare(strict_types=1);

use Nexus\Analytics\Core\Engine\AnalyticsEngine;
use Nexus\Analytics\Core\ValueObjects\QueryDefinition;
use Nexus\Analytics\Core\ValueObjects\QueryResult;

class FakeQuery
{
    public function where(...$args)
    {
        return $this;
    }

    public function get()
    {
        return collect([['id' => 1, 'value' => 'x']]);
    }

    public function count()
    {
        return 1;
    }
}

class FakeModel
{
    public function newQuery()
    {
        return new FakeQuery();
    }

    public function getKey()
    {
        return 1;
    }
}

it('runs a simple query using select closure', function () {
    $engine = new AnalyticsEngine();

    $def = ['select' => function ($q) { return $q->get(); }, 'model' => new FakeModel()];
    $qDef = new QueryDefinition('demo', $def);

    $res = $engine->execute($qDef);

    expect($res)->toBeInstanceOf(QueryResult::class);
    expect($res->rows)->toBeArray();
    expect(count($res->rows))->toBe(1);
});

it('applies guard rules and denies when guard fails', function () {
    $engine = new AnalyticsEngine();

    $guard = function ($q) { return false; };

    $def = ['select' => function ($q) { return $q->get(); }, 'model' => new FakeModel(), 'guards' => [$guard]];
    $qDef = new QueryDefinition('demo', $def);

    $res = $engine->execute($qDef);

    expect($res->rows)->toBeArray();
    expect(count($res->rows))->toBe(0);
});
