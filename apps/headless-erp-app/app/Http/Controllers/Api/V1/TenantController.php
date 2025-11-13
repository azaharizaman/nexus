<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use Nexus\Erp\Core\Actions\ArchiveTenantAction;
use Nexus\Erp\Core\Actions\CreateTenantAction;
use Nexus\Erp\Core\Actions\UpdateTenantAction;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenant Controller
 *
 * RESTful API endpoints for tenant management.
 * All endpoints require admin authorization.
 */
class TenantController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  TenantRepositoryContract  $repository  The tenant repository
     */
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {
        //
    }

    /**
     * Display a listing of tenants.
     *
     * Supports pagination, filtering by status and search term, and sorting.
     *
     * @param  Request  $request  The HTTP request
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by name or domain
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = ['name', 'domain', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Include soft deleted if requested
        if ($request->boolean('with_archived')) {
            $query->withTrashed();
        }

        // Eager load relationships if requested
        if ($request->boolean('with_users_count')) {
            $query->withCount('users');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $tenants = $query->paginate($perPage);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant.
     *
     * @param  StoreTenantRequest  $request  The validated request
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = CreateTenantAction::run($request->validated());

        return TenantResource::make($tenant)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tenant.
     *
     * @param  string  $id  The tenant UUID
     */
    public function show(string $id): TenantResource
    {
        $tenant = $this->repository->findById($id);

        abort_if(! $tenant, 404, 'Tenant not found');

        $this->authorize('view', $tenant);

        // Eager load users count if requested
        if (request('with_users_count')) {
            $tenant->loadCount(['users' => function ($query) {
                $query->withoutGlobalScope(\Nexus\Erp\Core\Scopes\TenantScope::class);
            }]);
        }

        return TenantResource::make($tenant);
    }

    /**
     * Update the specified tenant.
     *
     * @param  UpdateTenantRequest  $request  The validated request
     * @param  string  $id  The tenant UUID
     */
    public function update(UpdateTenantRequest $request, string $id): TenantResource
    {
        $tenant = $this->repository->findById($id);

        abort_if(! $tenant, 404, 'Tenant not found');

        $this->authorize('update', $tenant);

        $updatedTenant = UpdateTenantAction::run($tenant, $request->validated());

        return TenantResource::make($updatedTenant);
    }

    /**
     * Remove the specified tenant (archive/soft delete).
     *
     * @param  string  $id  The tenant UUID
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = $this->repository->findById($id);

        abort_if(! $tenant, 404, 'Tenant not found');

        $this->authorize('delete', $tenant);

        ArchiveTenantAction::run($tenant);

        return response()->json([
            'message' => 'Tenant archived successfully',
        ], 200);
    }
}
