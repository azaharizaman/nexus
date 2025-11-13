<?php

declare(strict_types=1);

use App\Models\User;
use App\Enums\UserStatus;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Create a test tenant
    $this->tenant = Tenant::factory()->create();

    // Create a test user
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'status' => UserStatus::ACTIVE,
    ]);
});

test('can login via API with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'expires_at',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'tenant_id',
                ],
            ],
        ]);
});

test('cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
        'device_name' => 'Test Device',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
});

test('cannot login with locked account', function () {
    $this->user->update([
        'locked_until' => now()->addMinutes(30),
        'failed_login_attempts' => 5,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(423)
        ->assertJsonStructure(['message']);
});

test('can logout with valid token', function () {
    $token = $this->user->createApiToken('Test Device');

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out']);
});

test('can get authenticated user profile', function () {
    $token = $this->user->createApiToken('Test Device');

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'tenant_id',
                'status',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
            ],
        ]);
});

test('cannot access protected endpoints without token', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

test('can register new user', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'tenant_id',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'tenant_id' => $this->tenant->id,
    ]);
});

test('cannot register with duplicate email in same tenant', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Duplicate User',
        'email' => 'test@example.com', // Already exists
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(422);
});

test('can request password reset', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message']);
});

test('rate limiting is enforced on auth endpoints', function () {
    // Make 6 requests (limit is 5)
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // The 6th request should be rate limited
    $response->assertStatus(429); // Too Many Requests
});

test('validates required fields on login', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name', 'tenant_id']);
});

test('validates email format on registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'New User',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validates password confirmation on registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
        'tenant_id' => $this->tenant->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
