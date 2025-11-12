<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\User\SuspendUserAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Support\Contracts\PermissionServiceContract;
use Azaharizaman\Erp\Core\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * User Management Controller
 *
 * Handles admin user management operations (CRUD, suspend, unlock).
 */
class UserManagementController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private readonly PermissionServiceContract $permissionService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * List users with pagination and filters
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Check authorization
        $this->authorize('viewAny', User::class);

        // Validate inputs
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(UserStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        // Build query
        $query = User::query();

        // Apply filters
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', "%{$validated['search']}%")
                    ->orWhere('email', 'like', "%{$validated['search']}%");
            });
        }

        // Paginate results
        $users = $query->paginate($validated['per_page'] ?? 15);

        return UserResource::collection($users);
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        // Check authorization
        $this->authorize('create', User::class);

        // Validate inputs
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $validated['tenant_id'],
            'status' => UserStatus::ACTIVE,
        ]);

        // Assign roles if provided (using batch assignment for transactional safety)
        if (! empty($validated['roles'])) {
            $this->permissionService->assignRoles($user, $validated['roles']);
        }

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a specific user
     */
    public function show(User $user): UserResource
    {
        // Check authorization
        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    /**
     * Update a user
     */
    public function update(Request $request, User $user): UserResource
    {
        // Check authorization
        $this->authorize('update', $user);

        // Validate inputs
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', Password::defaults()],
            'status' => ['sometimes', 'string', Rule::in(UserStatus::values())],
        ]);

        // Update user
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return UserResource::make($user);
    }

    /**
     * Delete a user (soft delete)
     */
    public function destroy(User $user): JsonResponse
    {
        // Check authorization
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Suspend a user
     */
    public function suspend(Request $request, User $user, SuspendUserAction $action): UserResource
    {
        // Check authorization
        $this->authorize('suspend', $user);

        // Validate inputs
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = $action->handle($user, $validated['reason']);

        return UserResource::make($user);
    }

    /**
     * Unlock a user account
     */
    public function unlock(User $user): UserResource
    {
        // Check authorization
        $this->authorize('update', $user);

        // Reset locked status
        $user->status = UserStatus::ACTIVE;
        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->save();

        return UserResource::make($user);
    }
}
