<?php

declare(strict_types=1);

use App\Actions\Auth\LoginAction;
use App\Exceptions\AccountLockedException;
use App\Models\User;
use Azaharizaman\Erp\Core\Enums\UserStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    // Create a test tenant
    $this->tenant = Tenant::factory()->create();

    // Create a test user
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'status' => UserStatus::ACTIVE,
        'failed_login_attempts' => 0,
        'locked_until' => null,
    ]);
});

test('can login with valid credentials', function () {
    $result = LoginAction::run(
        email: 'test@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['token', 'user', 'expires_at'])
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->id)->toBe($this->user->id);
});

test('throws exception when credentials are invalid', function () {
    LoginAction::run(
        email: 'test@example.com',
        password: 'wrongpassword',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );
})->throws(ValidationException::class);

test('throws exception when user does not exist', function () {
    LoginAction::run(
        email: 'nonexistent@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );
})->throws(ValidationException::class);

test('throws exception when account is locked', function () {
    $this->user->update([
        'locked_until' => now()->addMinutes(30),
        'failed_login_attempts' => 5,
    ]);

    LoginAction::run(
        email: 'test@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );
})->throws(AccountLockedException::class);

test('throws exception when account is inactive', function () {
    $this->user->update(['status' => UserStatus::INACTIVE]);

    LoginAction::run(
        email: 'test@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );
})->throws(ValidationException::class);

test('increments failed login attempts on wrong password', function () {
    try {
        LoginAction::run(
            email: 'test@example.com',
            password: 'wrongpassword',
            deviceName: 'Test Device',
            tenantId: $this->tenant->id
        );
    } catch (ValidationException $e) {
        // Expected exception
    }

    $this->user->refresh();
    expect($this->user->failed_login_attempts)->toBe(1);
});

test('resets failed login attempts on successful login', function () {
    $this->user->update(['failed_login_attempts' => 3]);

    LoginAction::run(
        email: 'test@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $this->tenant->id
    );

    $this->user->refresh();
    expect($this->user->failed_login_attempts)->toBe(0);
});

test('locks account after 5 failed attempts', function () {
    // Fail 5 times
    for ($i = 0; $i < 5; $i++) {
        try {
            LoginAction::run(
                email: 'test@example.com',
                password: 'wrongpassword',
                deviceName: 'Test Device',
                tenantId: $this->tenant->id
            );
        } catch (ValidationException $e) {
            // Expected
        }
    }

    $this->user->refresh();
    expect($this->user->failed_login_attempts)->toBe(5)
        ->and($this->user->locked_until)->not->toBeNull()
        ->and($this->user->isLocked())->toBeTrue();
});

test('respects tenant isolation', function () {
    $otherTenant = Tenant::factory()->create();

    LoginAction::run(
        email: 'test@example.com',
        password: 'password123',
        deviceName: 'Test Device',
        tenantId: $otherTenant->id
    );
})->throws(ValidationException::class, 'The provided credentials are incorrect');
