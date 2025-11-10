# Laravel Sanctum Integration

## Overview

Laravel Sanctum is configured for stateless API token authentication with an 8-hour token expiration policy. This document provides guidance on using Sanctum for API authentication in the Laravel ERP system.

## Configuration

### Token Expiration

Tokens automatically expire after **8 hours (480 minutes)** by default. This can be configured via the `SANCTUM_EXPIRATION_MINUTES` environment variable.

```env
# .env
SANCTUM_EXPIRATION_MINUTES=480  # 8 hours
```

To disable automatic expiration, set it to `null`:

```env
SANCTUM_EXPIRATION_MINUTES=null
```

### Stateful Domains

Configure domains that can access the API with cookie-based authentication:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1
```

### Token Prefix (Optional)

Add a prefix to tokens for improved security scanning:

```env
SANCTUM_TOKEN_PREFIX=erp_
```

## Creating API Tokens

### Basic Token Creation

```php
use App\Models\User;

$user = User::find(1);

// Create a token with all abilities (*)
$token = $user->createToken('mobile-app');
$plainTextToken = $token->plainTextToken;

// Return token to user (only shown once)
return response()->json([
    'token' => $plainTextToken,
    'type' => 'Bearer',
]);
```

### Token with Specific Abilities

```php
// Create a read-only token
$token = $user->createToken('read-only-token', [
    'tenant:read',
    'user:read',
    'inventory:read',
]);

// Create a token with write permissions
$token = $user->createToken('write-token', [
    'tenant:read',
    'tenant:write',
    'user:read',
    'user:write',
]);
```

## Token Abilities (Scopes)

Token abilities follow the pattern `domain:action` for consistency.

### Core Domain

- `tenant:read` - Read tenant information
- `tenant:write` - Create/update tenants (admin only)
- `tenant:delete` - Delete/archive tenants (admin only)
- `user:read` - Read user information
- `user:write` - Create/update users
- `user:delete` - Delete/deactivate users
- `settings:read` - Read system settings
- `settings:write` - Modify system settings

### Inventory Domain

- `inventory:read` - Read inventory items
- `inventory:write` - Create/update inventory items
- `warehouse:read` - Read warehouse information
- `warehouse:write` - Create/update warehouses
- `stock:read` - Read stock levels
- `stock:write` - Adjust stock levels

### Sales Domain

- `customer:read` - Read customer information
- `customer:write` - Create/update customers
- `quotation:read` - Read sales quotations
- `quotation:write` - Create/update quotations
- `order:read` - Read sales orders
- `order:write` - Create/update orders

### Purchasing Domain

- `vendor:read` - Read vendor information
- `vendor:write` - Create/update vendors
- `purchase:read` - Read purchase orders
- `purchase:write` - Create/update purchase orders
- `goods-receipt:read` - Read goods receipts
- `goods-receipt:write` - Create goods receipts

### Special Abilities

- `*` - Grants all abilities (use with caution)
- `api:full-access` - Full API access for trusted integrations
- `api:read-only` - Read-only access to all resources

## Checking Token Abilities

### In Controllers

```php
public function store(Request $request)
{
    // Check if token has specific ability
    if (!auth()->user()->tokenCan('tenant:write')) {
        abort(403, 'Insufficient permissions');
    }
    
    // Your code here...
}
```

### Using Middleware

```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'abilities:tenant:write'])
    ->post('/tenants', [TenantController::class, 'store']);

// Multiple abilities (user must have ALL)
Route::middleware(['auth:sanctum', 'abilities:tenant:read,tenant:write'])
    ->group(function () {
        // Routes here...
    });

// Any of the abilities (user must have at least ONE)
Route::middleware(['auth:sanctum', 'ability:tenant:read,tenant:write'])
    ->group(function () {
        // Routes here...
    });
```

## Token Management

### Listing User Tokens

```php
$user = auth()->user();
$tokens = $user->tokens;

foreach ($tokens as $token) {
    echo "Token: {$token->name}\n";
    echo "Abilities: " . implode(', ', $token->abilities) . "\n";
    echo "Last used: {$token->last_used_at}\n";
}
```

### Revoking Tokens

```php
// Revoke all tokens for a user
auth()->user()->tokens()->delete();

// Revoke specific token
$tokenId = request()->input('token_id');
auth()->user()->tokens()->where('id', $tokenId)->delete();

// Revoke current token
request()->user()->currentAccessToken()->delete();
```

## API Authentication

### Making Authenticated Requests

Include the token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     https://api.example.com/api/v1/tenants
```

### In JavaScript

```javascript
fetch('https://api.example.com/api/v1/tenants', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    }
});
```

### In PHP/Guzzle

```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.example.com/api/v1/tenants', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ],
]);
```

## Security Best Practices

1. **Never expose plain text tokens** - Tokens are only visible when first created
2. **Use specific abilities** - Don't use wildcard `*` unless absolutely necessary
3. **Implement token rotation** - Regularly revoke and recreate tokens
4. **Monitor token usage** - Check `last_used_at` timestamps
5. **Set appropriate expiration** - Balance security with usability (default: 8 hours)
6. **Use HTTPS** - Always transmit tokens over secure connections
7. **Implement rate limiting** - Protect against brute force attacks
8. **Log authentication events** - Track token creation, usage, and revocation

## Testing

### Testing with Sanctum

```php
use Laravel\Sanctum\Sanctum;

test('user can access protected route', function () {
    $user = User::factory()->create();
    
    Sanctum::actingAs($user, ['tenant:read']);
    
    $response = $this->getJson('/api/v1/tenants');
    
    $response->assertOk();
});
```

### Testing Token Creation

```php
test('user can create token with abilities', function () {
    $user = User::factory()->create();
    
    $token = $user->createToken('test-token', ['tenant:read']);
    
    expect($token->plainTextToken)->not->toBeNull();
    expect($token->accessToken->abilities)->toBe(['tenant:read']);
});
```

## Troubleshooting

### 401 Unauthorized

- Verify token is included in `Authorization` header
- Check token format: `Bearer YOUR_TOKEN_HERE`
- Ensure token hasn't expired
- Verify token exists in database

### 403 Forbidden

- Check if token has required abilities
- Verify user has proper permissions
- Check authorization policies

### Token Not Working

```php
// Debug token information
$user = auth()->user();
if ($user) {
    dump($user->currentAccessToken());
    dump($user->tokenCan('required:ability'));
}
```

## References

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Token Abilities Documentation](https://laravel.com/docs/sanctum#token-abilities)
- Configuration: `config/sanctum.php`
- Tests: `tests/Feature/Auth/`
