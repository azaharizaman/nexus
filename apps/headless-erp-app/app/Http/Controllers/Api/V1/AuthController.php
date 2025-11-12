<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\RequestPasswordResetAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Exceptions\AccountLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\TokenResource;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attribute\Get;
use Illuminate\Routing\Attribute\Middleware;
use Illuminate\Routing\Attribute\Post;
use Illuminate\Routing\Attribute\Prefix;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 *
 * Handles user authentication, registration, and password management.
 * All endpoints except protected ones are publicly accessible.
 */
#[Prefix('api/v1/auth')]
class AuthController extends Controller
{
    /**
     * Login user and generate API token
     */
    #[Post('/login', name: 'api.v1.auth.login')]
    #[Middleware(['throttle:auth'])]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = LoginAction::run(
                email: $request->input('email'),
                password: $request->input('password'),
                deviceName: $request->input('device_name'),
                tenantId: $request->input('tenant_id')
            );

            return TokenResource::make($result)
                ->response()
                ->setStatusCode(200);
        } catch (AccountLockedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 423);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 401);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout user and revoke token
     */
    #[Post('/logout', name: 'api.v1.auth.logout')]
    #[Middleware(['auth:sanctum', 'auth.locked'])]
    public function logout(): JsonResponse
    {
        $user = auth()->user();

        if ($user) {
            LogoutAction::run($user);
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ], 200);
    }

    /**
     * Register a new user account
     */
    #[Post('/register', name: 'api.v1.auth.register')]
    #[Middleware(['throttle:auth'])]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = RegisterUserAction::run($request->validated());

            return UserResource::make($user)
                ->response()
                ->setStatusCode(201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get authenticated user profile
     */
    #[Get('/me', name: 'api.v1.auth.me')]
    #[Middleware(['auth:sanctum', 'auth.locked'])]
    public function me(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Load relationships for complete user data
        $user->load(['tenant', 'roles', 'permissions']);

        return UserResource::make($user)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Request password reset
     */
    #[Post('/password/forgot', name: 'api.v1.auth.password.forgot')]
    #[Middleware(['throttle:auth'])]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            RequestPasswordResetAction::run(
                email: $request->input('email'),
                tenantId: $request->input('tenant_id')
            );

            return response()->json([
                'message' => 'Password reset link sent to your email',
            ], 200);
        } catch (\RuntimeException $e) {
            // Don't reveal if user exists for security
            return response()->json([
                'message' => 'If the email exists, a password reset link has been sent',
            ], 200);
        }
    }

    /**
     * Reset password with token
     */
    #[Post('/password/reset', name: 'api.v1.auth.password.reset')]
    #[Middleware(['throttle:auth'])]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            ResetPasswordAction::run(
                email: $request->input('email'),
                token: $request->input('token'),
                newPassword: $request->input('password'),
                tenantId: $request->input('tenant_id')
            );

            return response()->json([
                'message' => 'Password has been reset successfully',
            ], 200);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Password reset failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
