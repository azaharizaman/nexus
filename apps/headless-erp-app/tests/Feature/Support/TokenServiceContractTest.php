<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Contracts\TokenServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tokenService = app(TokenServiceContract::class);
    $this->user = User::factory()->create();
});

test('can create token for user', function () {
    $tokenString = $this->tokenService->createToken($this->user, 'test-device');

    expect($tokenString)->toBeString()
        ->and($tokenString)->not->toBeEmpty();
});

test('can create token with specific abilities', function () {
    $abilities = ['read', 'write', 'delete'];

    $tokenString = $this->tokenService->createToken($this->user, 'test-device', $abilities);

    expect($tokenString)->toBeString()
        ->and($tokenString)->not->toBeEmpty();

    // Verify token has correct abilities
    $tokens = $this->tokenService->getActiveTokens($this->user);
    $token = $tokens->first();

    expect($token->abilities)->toBe($abilities);
});

test('can create token with wildcard abilities', function () {
    $tokenString = $this->tokenService->createToken($this->user, 'test-device', ['*']);

    $tokens = $this->tokenService->getActiveTokens($this->user);
    $token = $tokens->first();

    expect($token->abilities)->toBe(['*']);
});

test('can revoke specific token', function () {
    // Create multiple tokens
    $this->tokenService->createToken($this->user, 'device-1');
    $this->tokenService->createToken($this->user, 'device-2');

    $tokens = $this->tokenService->getActiveTokens($this->user);
    expect($tokens)->toHaveCount(2);

    // Revoke first token
    $tokenToRevoke = $tokens->first();
    $result = $this->tokenService->revokeToken($this->user, $tokenToRevoke->id);

    expect($result)->toBeTrue();

    // Should have one token remaining
    $remainingTokens = $this->tokenService->getActiveTokens($this->user);
    expect($remainingTokens)->toHaveCount(1);
});

test('can revoke all tokens', function () {
    // Create multiple tokens
    $this->tokenService->createToken($this->user, 'device-1');
    $this->tokenService->createToken($this->user, 'device-2');
    $this->tokenService->createToken($this->user, 'device-3');

    $tokens = $this->tokenService->getActiveTokens($this->user);
    expect($tokens)->toHaveCount(3);

    // Revoke all tokens
    $result = $this->tokenService->revokeAllTokens($this->user);

    expect($result)->toBeTrue();

    // Should have no tokens
    $remainingTokens = $this->tokenService->getActiveTokens($this->user);
    expect($remainingTokens)->toHaveCount(0);
});

test('get active tokens returns empty collection for user with no tokens', function () {
    $newUser = User::factory()->create();

    $tokens = $this->tokenService->getActiveTokens($newUser);

    expect($tokens)->toBeInstanceOf(Collection::class)
        ->and($tokens)->toHaveCount(0);
});

test('revoke token returns false for non-existent token', function () {
    $result = $this->tokenService->revokeToken($this->user, 99999);

    expect($result)->toBeFalse();
});

test('can check token abilities', function () {
    $tokenString = $this->tokenService->createToken($this->user, 'test-device', ['read', 'write']);

    // Authenticate with the token
    $this->actingAs($this->user, 'sanctum');

    expect($this->tokenService->tokenCan($this->user, 'read'))->toBeTrue()
        ->and($this->tokenService->tokenCan($this->user, 'write'))->toBeTrue()
        ->and($this->tokenService->tokenCan($this->user, 'delete'))->toBeFalse();
});

test('can get current access token', function () {
    $tokenString = $this->tokenService->createToken($this->user, 'test-device');

    // The token is not set in request context yet, so should be null
    $currentToken = $this->tokenService->currentAccessToken($this->user);

    expect($currentToken)->toBeNull();
});

test('token service handles multiple users independently', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->tokenService->createToken($user1, 'user1-device');
    $this->tokenService->createToken($user2, 'user2-device-1');
    $this->tokenService->createToken($user2, 'user2-device-2');

    $user1Tokens = $this->tokenService->getActiveTokens($user1);
    $user2Tokens = $this->tokenService->getActiveTokens($user2);

    expect($user1Tokens)->toHaveCount(1)
        ->and($user2Tokens)->toHaveCount(2);
});

test('can create tokens with same name for same user', function () {
    // Some implementations allow duplicate token names
    $this->tokenService->createToken($this->user, 'device');
    $this->tokenService->createToken($this->user, 'device');

    $tokens = $this->tokenService->getActiveTokens($this->user);

    expect($tokens->count())->toBeGreaterThanOrEqual(2);
});

test('revoke all tokens returns false when user has no tokens', function () {
    $newUser = User::factory()->create();

    $result = $this->tokenService->revokeAllTokens($newUser);

    // Should return false when no tokens to revoke
    expect($result)->toBeFalse();
});

test('token string format is valid', function () {
    $tokenString = $this->tokenService->createToken($this->user, 'test-device');

    // Sanctum tokens are typically in format: plainTextToken
    expect($tokenString)->toBeString()
        ->and(strlen($tokenString))->toBeGreaterThan(40); // Tokens are typically long
});
