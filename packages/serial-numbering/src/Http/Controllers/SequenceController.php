<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Http\Controllers;

use Nexus\Erp\SerialNumbering\Actions\GenerateSerialNumberAction;
use Nexus\Erp\SerialNumbering\Actions\OverrideSerialNumberAction;
use Nexus\Erp\SerialNumbering\Actions\PreviewSerialNumberAction;
use Nexus\Erp\SerialNumbering\Actions\ResetSequenceAction;
use Nexus\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Nexus\Erp\SerialNumbering\Http\Requests\CreateSequenceRequest;
use Nexus\Erp\SerialNumbering\Http\Requests\UpdateSequenceRequest;
use Nexus\Erp\SerialNumbering\Http\Resources\SequenceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Sequence Controller
 *
 * Handles API endpoints for sequence management.
 */
class SequenceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  SequenceRepositoryContract  $repository  The sequence repository
     */
    public function __construct(
        private readonly SequenceRepositoryContract $repository
    ) {
        // Authorization is handled by policies and middleware
    }

    /**
     * List all sequences for the current tenant.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $sequences = $this->repository->getAllForTenant($tenantId);

        return response()->json([
            'data' => SequenceResource::collection($sequences),
        ]);
    }

    /**
     * Create a new sequence configuration.
     *
     * @param  CreateSequenceRequest  $request  The validated request
     * @return JsonResponse
     */
    public function store(CreateSequenceRequest $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $data = array_merge($request->validated(), [
            'tenant_id' => $tenantId,
            'reset_period' => $request->input('reset_period', config('serial-numbering.default_reset_period')),
            'padding' => $request->input('padding', config('serial-numbering.default_padding')),
            'current_value' => 0,
        ]);

        $sequence = $this->repository->create($data);

        return (new SequenceResource($sequence))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a specific sequence.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function show(Request $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            return response()->json([
                'message' => 'Sequence not found',
            ], 404);
        }

        return response()->json([
            'data' => new SequenceResource($sequence),
        ]);
    }

    /**
     * Update a sequence configuration.
     *
     * @param  UpdateSequenceRequest  $request  The validated request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function update(UpdateSequenceRequest $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            return response()->json([
                'message' => 'Sequence not found',
            ], 404);
        }

        $this->repository->update($sequence, $request->validated());

        return response()->json([
            'data' => new SequenceResource($sequence->fresh()),
        ]);
    }

    /**
     * Delete a sequence configuration.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function destroy(Request $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $deleted = $this->repository->delete($tenantId, $sequenceName);

        if (! $deleted) {
            return response()->json([
                'message' => 'Sequence not found',
            ], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * Generate a new serial number.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function generate(Request $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $context = $request->input('context', []);

        $generatedNumber = GenerateSerialNumberAction::run($tenantId, $sequenceName, $context);

        return response()->json([
            'data' => [
                'generated_number' => $generatedNumber,
                'sequence_name' => $sequenceName,
            ],
        ], 201);
    }

    /**
     * Preview the next serial number without consuming the counter.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function preview(Request $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $context = $request->input('context', []);

        $previewNumber = PreviewSerialNumberAction::run($tenantId, $sequenceName, $context);

        return response()->json([
            'data' => [
                'preview_number' => $previewNumber,
                'sequence_name' => $sequenceName,
            ],
        ]);
    }

    /**
     * Reset a sequence counter to zero.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function reset(Request $request, string $sequenceName): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $reason = $request->input('reason', 'Manual reset');

        ResetSequenceAction::run($tenantId, $sequenceName, $reason);

        return response()->json([
            'message' => 'Sequence reset successfully',
        ]);
    }

    /**
     * Override a serial number manually.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $sequenceName  The sequence name
     * @return JsonResponse
     */
    public function override(Request $request, string $sequenceName): JsonResponse
    {
        $request->validate([
            'override_number' => 'required|string|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        $tenantId = $request->get('tenant_id');
        $overrideNumber = $request->input('override_number');
        $reason = $request->input('reason');

        OverrideSerialNumberAction::run($tenantId, $sequenceName, $overrideNumber, $reason);

        return response()->json([
            'message' => 'Serial number overridden successfully',
            'data' => [
                'override_number' => $overrideNumber,
            ],
        ]);
    }
}
