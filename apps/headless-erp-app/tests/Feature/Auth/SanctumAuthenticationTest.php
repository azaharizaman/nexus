<?php

declare(strict_types=1);

use App\Models\User;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->adminUser = User::factory()->admin()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

test('authenticated user can access protected routes', function () {
    Sanctum::actingAs($this->adminUser);

    $response = $this->getJson('/api/v1/tenants');

    $response->assertSuccessful();
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->getJson('/api/v1/tenants');

    $response->assertUnauthorized();
});

test('user can create token with abilities', function () {
    $token = $this->user->createToken('test-token', ['tenant:read', 'tenant:write']);

    expect($token->plainTextToken)->not->toBeNull();
    expect($token->accessToken->abilities)->toBe(['tenant:read', 'tenant:write']);
});

test('user can create token without abilities', function () {
    $token = $this->user->createToken('test-token');

    expect($token->plainTextToken)->not->toBeNull();
    expect($token->accessToken->abilities)->toBe(['*']);
});

test('token abilities are properly checked', function () {
    $token = $this->user->createToken('test-token', ['tenant:read']);

    Sanctum::actingAs($this->user, ['tenant:read']);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeFalse();
});

test('wildcard ability grants all access', function () {
    Sanctum::actingAs($this->user, ['*']);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('any:ability'))->toBeTrue();
});

test('token expiration is configured', function () {
    $expirationMinutes = config('sanctum.expiration');

    expect($expirationMinutes)->not->toBeNull();
    expect((int) $expirationMinutes)->toBe(480); // 8 hours
});

test('user can have multiple tokens', function () {
    $token1 = $this->user->createToken('token-1', ['tenant:read']);
    $token2 = $this->user->createToken('token-2', ['tenant:write']);

    expect($this->user->tokens)->toHaveCount(2);
    expect($token1->plainTextToken)->not->toBe($token2->plainTextToken);
});

test('user can revoke token', function () {
    $token = $this->user->createToken('test-token');

    expect($this->user->tokens)->toHaveCount(1);

    $this->user->tokens()->delete();

    expect($this->user->fresh()->tokens)->toHaveCount(0);
});

test('user can revoke specific token', function () {
    $token1 = $this->user->createToken('token-1');
    $token2 = $this->user->createToken('token-2');

    expect($this->user->tokens)->toHaveCount(2);

    $token1->accessToken->delete();

    expect($this->user->fresh()->tokens)->toHaveCount(1);
    // After deleting token-1, only token-2 remains (latest)
    $latestToken = $this->user->tokens()->latest()->first();
    expect($latestToken->name)->toBe('token-2');
});

test('tokens can have descriptive names', function () {
    $token = $this->user->createToken('mobile-app-token');

    expect($token->accessToken->name)->toBe('mobile-app-token');
});

test('different users have isolated tokens', function () {
    $user2 = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->user->createToken('user1-token');
    $user2->createToken('user2-token');

    expect($this->user->tokens)->toHaveCount(1);
    expect($user2->tokens)->toHaveCount(1);
    expect($this->user->tokens->first()->id)->not->toBe($user2->tokens->first()->id);
});

test('token can be used for api authentication', function () {
    $token = $this->adminUser->createToken('api-token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/v1/tenants');

    $response->assertSuccessful();
});

test('invalid token is rejected', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-string',
    ])->getJson('/api/v1/tenants');

    $response->assertUnauthorized();
});

test('sanctum stateful domains is configured', function () {
    $statefulDomains = config('sanctum.stateful');

    expect($statefulDomains)->toBeArray();
    expect($statefulDomains)->toContain('localhost');
    expect($statefulDomains)->toContain('127.0.0.1');
});

test('sanctum guard is configured', function () {
    $guard = config('sanctum.guard');

    expect($guard)->toBeArray();
    expect($guard)->toContain('web');
});

test('sanctum middleware configuration exists', function () {
    $middleware = config('sanctum.middleware');

    expect($middleware)->toBeArray();
    expect($middleware)->toHaveKey('authenticate_session');
    expect($middleware)->toHaveKey('encrypt_cookies');
    expect($middleware)->toHaveKey('validate_csrf_token');
});
