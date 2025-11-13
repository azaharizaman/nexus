<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Http\Controllers;

use Nexus\Erp\Core\Actions\ActivateTenantAction;
use Nexus\Erp\Core\Actions\ArchiveTenantAction;
use Nexus\Erp\Core\Actions\CreateTenantAction;
use Nexus\Erp\Core\Actions\DeleteTenantAction;
use Nexus\Erp\Core\Actions\EndImpersonationAction;
use Nexus\Erp\Core\Actions\StartImpersonationAction;
use Nexus\Erp\Core\Actions\SuspendTenantAction;
use Nexus\Erp\Core\Actions\UpdateTenantAction;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Http\Requests\StoreTenantRequest;
use Nexus\Erp\Core\Http\Requests\UpdateTenantRequest;
use Nexus\Erp\Core\Http\Resources\TenantResource;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * Tenant Controller
 *
 * RESTful API endpoints for tenant management.
 * Handles CRUD operations and tenant lifecycle management.
 */
class TenantController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {
        // Apply authentication middleware to all routes
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of tenants
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // Check authorization
        if (! auth()->user()->can('view-tenants')) {
            abort(403, 'Unauthorized to view tenants');
        }

        // Validate input
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $filters = [
            'status' => $validated['status'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $tenants = $this->repository->paginate($perPage, $filters);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant
     */
    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $tenant = $action->handle($request->validated());

        return TenantResource::make($tenant)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tenant
     */
    public function show(Tenant $tenant): TenantResource
    {
        $this->authorize('view-tenant', $tenant);

        return TenantResource::make($tenant);
    }

    /**
     * Update the specified tenant
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenantAction $action): TenantResource
    {
        $tenant = $action->handle($tenant, $request->validated());

        return TenantResource::make($tenant);
    }

    /**
     * Remove the specified tenant
     */
    public function destroy(Tenant $tenant, DeleteTenantAction $action): JsonResponse
    {
        $this->authorize('delete-tenant', $tenant);

        $action->handle($tenant);

        return response()->json(null, 204);
    }

    /**
     * Suspend the specified tenant
     */
    public function suspend(Request $request, Tenant $tenant, SuspendTenantAction $action): TenantResource
    {
        $this->authorize('suspend-tenant', $tenant);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Activate the specified tenant
     */
    public function activate(Request $request, Tenant $tenant, ActivateTenantAction $action): TenantResource
    {
        $this->authorize('activate-tenant', $tenant);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason', 'Manual activation'));

        return TenantResource::make($tenant);
    }

    /**
     * Archive the specified tenant
     */
    public function archive(Request $request, Tenant $tenant, ArchiveTenantAction $action): TenantResource
    {
        $this->authorize('archive-tenant', $tenant);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Start impersonating the specified tenant
     */
    public function impersonate(Request $request, Tenant $tenant, StartImpersonationAction $action): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $action->handle($request->user(), $tenant, $request->input('reason'));

        return response()->json([
            'message' => 'Impersonation started successfully.',
            'tenant' => TenantResource::make($tenant),
        ]);
    }

    /**
     * End the current impersonation session
     */
    public function endImpersonation(Request $request, EndImpersonationAction $action): JsonResponse
    {
        $action->handle($request->user());

        return response()->json([
            'message' => 'Impersonation ended successfully.',
        ]);
    }
}
