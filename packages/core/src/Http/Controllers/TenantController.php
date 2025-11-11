<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Http\Controllers;

use Azaharizaman\Erp\Core\Actions\ActivateTenantAction;
use Azaharizaman\Erp\Core\Actions\ArchiveTenantAction;
use Azaharizaman\Erp\Core\Actions\CreateTenantAction;
use Azaharizaman\Erp\Core\Actions\DeleteTenantAction;
use Azaharizaman\Erp\Core\Actions\EndImpersonationAction;
use Azaharizaman\Erp\Core\Actions\StartImpersonationAction;
use Azaharizaman\Erp\Core\Actions\SuspendTenantAction;
use Azaharizaman\Erp\Core\Actions\UpdateTenantAction;
use Azaharizaman\Erp\Core\Http\Requests\StoreTenantRequest;
use Azaharizaman\Erp\Core\Http\Requests\UpdateTenantRequest;
use Azaharizaman\Erp\Core\Http\Resources\TenantResource;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
    public function __construct()
    {
        // Apply authentication middleware to all routes
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of tenants
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $tenants = Tenant::query()
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant
     *
     * @param  StoreTenantRequest  $request
     * @param  CreateTenantAction  $action
     * @return JsonResponse
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
     *
     * @param  Tenant  $tenant
     * @return TenantResource
     */
    public function show(Tenant $tenant): TenantResource
    {
        return TenantResource::make($tenant);
    }

    /**
     * Update the specified tenant
     *
     * @param  UpdateTenantRequest  $request
     * @param  Tenant  $tenant
     * @param  UpdateTenantAction  $action
     * @return TenantResource
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenantAction $action): TenantResource
    {
        $tenant = $action->handle($tenant, $request->validated());

        return TenantResource::make($tenant);
    }

    /**
     * Remove the specified tenant
     *
     * @param  Tenant  $tenant
     * @param  DeleteTenantAction  $action
     * @return JsonResponse
     */
    public function destroy(Tenant $tenant, DeleteTenantAction $action): JsonResponse
    {
        $action->handle($tenant);

        return response()->json(null, 204);
    }

    /**
     * Suspend the specified tenant
     *
     * @param  Request  $request
     * @param  Tenant  $tenant
     * @param  SuspendTenantAction  $action
     * @return TenantResource
     */
    public function suspend(Request $request, Tenant $tenant, SuspendTenantAction $action): TenantResource
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Activate the specified tenant
     *
     * @param  Tenant  $tenant
     * @param  ActivateTenantAction  $action
     * @return TenantResource
     */
    public function activate(Tenant $tenant, ActivateTenantAction $action): TenantResource
    {
        $tenant = $action->handle($tenant);

        return TenantResource::make($tenant);
    }

    /**
     * Archive the specified tenant
     *
     * @param  Request  $request
     * @param  Tenant  $tenant
     * @param  ArchiveTenantAction  $action
     * @return TenantResource
     */
    public function archive(Request $request, Tenant $tenant, ArchiveTenantAction $action): TenantResource
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Start impersonating the specified tenant
     *
     * @param  Request  $request
     * @param  Tenant  $tenant
     * @param  StartImpersonationAction  $action
     * @return JsonResponse
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
     *
     * @param  Request  $request
     * @param  EndImpersonationAction  $action
     * @return JsonResponse
     */
    public function endImpersonation(Request $request, EndImpersonationAction $action): JsonResponse
    {
        $action->handle($request->user());

        return response()->json([
            'message' => 'Impersonation ended successfully.',
        ]);
    }
}
