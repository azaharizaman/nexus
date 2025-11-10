<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    | Default: 480 minutes (8 hours) for security and usability balance.
    | Set to null to disable automatic expiration.
    |
    */

    'expiration' => env('SANCTUM_EXPIRATION_MINUTES', 480),

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Abilities (Scopes)
    |--------------------------------------------------------------------------
    |
    | This section documents the available token abilities/scopes for fine-grained
    | access control. Abilities are used when creating tokens to restrict what
    | actions the token holder can perform.
    |
    | Usage Example:
    |   $token = $user->createToken('api-token', ['tenant:read', 'tenant:write']);
    |
    | Checking abilities in controllers:
    |   if (!auth()->user()->tokenCan('tenant:write')) {
    |       abort(403, 'Insufficient permissions');
    |   }
    |
    | Or using middleware:
    |   Route::middleware(['auth:sanctum', 'abilities:tenant:write'])->group(...);
    |
    | Available Abilities:
    |
    | Core Domain:
    |   - 'tenant:read'        - Read tenant information
    |   - 'tenant:write'       - Create/update tenants (admin only)
    |   - 'tenant:delete'      - Delete/archive tenants (admin only)
    |   - 'user:read'          - Read user information
    |   - 'user:write'         - Create/update users
    |   - 'user:delete'        - Delete/deactivate users
    |   - 'settings:read'      - Read system settings
    |   - 'settings:write'     - Modify system settings
    |
    | Backoffice Domain:
    |   - 'company:read'       - Read company information
    |   - 'company:write'      - Create/update companies
    |   - 'office:read'        - Read office information
    |   - 'office:write'       - Create/update offices
    |   - 'department:read'    - Read department information
    |   - 'department:write'   - Create/update departments
    |   - 'staff:read'         - Read staff information
    |   - 'staff:write'        - Create/update staff
    |
    | Inventory Domain:
    |   - 'inventory:read'     - Read inventory items
    |   - 'inventory:write'    - Create/update inventory items
    |   - 'warehouse:read'     - Read warehouse information
    |   - 'warehouse:write'    - Create/update warehouses
    |   - 'stock:read'         - Read stock levels
    |   - 'stock:write'        - Adjust stock levels
    |
    | Sales Domain:
    |   - 'customer:read'      - Read customer information
    |   - 'customer:write'     - Create/update customers
    |   - 'quotation:read'     - Read sales quotations
    |   - 'quotation:write'    - Create/update quotations
    |   - 'order:read'         - Read sales orders
    |   - 'order:write'        - Create/update orders
    |
    | Purchasing Domain:
    |   - 'vendor:read'        - Read vendor information
    |   - 'vendor:write'       - Create/update vendors
    |   - 'purchase:read'      - Read purchase orders
    |   - 'purchase:write'     - Create/update purchase orders
    |   - 'goods-receipt:read' - Read goods receipts
    |   - 'goods-receipt:write' - Create goods receipts
    |
    | Accounting Domain:
    |   - 'accounting:read'    - Read financial data
    |   - 'accounting:write'   - Create/update financial transactions
    |   - 'reports:read'       - Read financial reports
    |
    | Special Abilities:
    |   - '*'                  - Grants all abilities (use with caution)
    |   - 'api:full-access'    - Full API access for trusted integrations
    |   - 'api:read-only'      - Read-only access to all resources
    |
    | Note: Abilities follow the pattern 'domain:action' for consistency.
    |       Always check abilities in addition to Laravel policies for defense in depth.
    |
    */

];
