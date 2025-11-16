<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AnalyticsSubject;
use Nexus\Atomy\Models\User;

uses(RefreshDatabase::class);

test('analytics runQuery persists history and tenant scope', function () {
    $user = User::factory()->create();

    $subject = AnalyticsSubject::factory()->create(['tenant_id' => $user->tenant_id]);

    $this->actingAs($user);

    $res = $subject->analytics()->runQuery('simple_list');

    // assert result rows are present
    expect($res->rows)->toBeArray();

    // history persisted and tenant id set
    $this->assertDatabaseHas('analytics_history', [
        'subject_type' => get_class($subject),
        'subject_id' => $subject->getKey(),
        'tenant_id' => $user->tenant_id,
    ]);
});

test('analytics can() returns true when guard allows', function () {
    $user = User::factory()->create();
    $subject = AnalyticsSubject::factory()->create(['tenant_id' => $user->tenant_id]);

    $this->actingAs($user);

    $allowed = $subject->analytics()->can('view');

    expect($allowed)->toBeTrue();
});
