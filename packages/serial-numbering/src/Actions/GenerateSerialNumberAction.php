<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Actions;

use Azaharizaman\Erp\SerialNumbering\Contracts\PatternParserContract;
use Azaharizaman\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Azaharizaman\Erp\SerialNumbering\Events\SequenceGeneratedEvent;
use Azaharizaman\Erp\SerialNumbering\Models\SerialNumberLog;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Generate Serial Number Action
 *
 * Generates a new serial number with atomic counter increment and
 * transaction-safe pattern evaluation.
 */
class GenerateSerialNumberAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param  SequenceRepositoryContract  $repository  The sequence repository
     * @param  PatternParserContract  $parser  The pattern parser
     */
    public function __construct(
        private readonly SequenceRepositoryContract $repository,
        private readonly PatternParserContract $parser
    ) {}

    /**
     * Handle the action.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  array<string, mixed>  $context  Additional context for pattern variables
     * @return string The generated serial number
     *
     * @throws \Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException
     * @throws \Azaharizaman\Erp\SerialNumbering\Exceptions\InvalidPatternException
     */
    public function handle(string $tenantId, string $sequenceName, array $context = []): string
    {
        return DB::transaction(function () use ($tenantId, $sequenceName, $context) {
            // Find sequence configuration
            $sequence = $this->repository->find($tenantId, $sequenceName);

            if ($sequence === null) {
                throw new \Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException(
                    "Sequence '{$sequenceName}' not found for tenant '{$tenantId}'"
                );
            }

            // Check if sequence should reset
            if ($sequence->shouldReset()) {
                $this->repository->reset($tenantId, $sequenceName);
                // Reload sequence after reset
                $sequence = $this->repository->find($tenantId, $sequenceName);
            }

            // Lock and increment counter atomically
            $newCounter = $this->repository->lockAndIncrement($tenantId, $sequenceName);

            // Build context for pattern evaluation
            $patternContext = array_merge([
                'counter' => $newCounter,
                'padding' => $sequence->padding,
                'tenant_code' => $context['tenant_code'] ?? '',
                'prefix' => $context['prefix'] ?? '',
                'department_code' => $context['department_code'] ?? '',
            ], $context);

            // Parse pattern to generate serial number
            $generatedNumber = $this->parser->parse($sequence->pattern, $patternContext);

            // Log generation
            if (config('serial-numbering.log_generations', true)) {
                $this->logGeneration($tenantId, $sequenceName, $generatedNumber, $context);
            }

            // Dispatch event
            event(new SequenceGeneratedEvent(
                $tenantId,
                $sequenceName,
                $generatedNumber,
                $newCounter
            ));

            return $generatedNumber;
        });
    }

    /**
     * Log the serial number generation.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $generatedNumber  The generated number
     * @param  array<string, mixed>  $context  Additional context
     * @return void
     */
    private function logGeneration(
        string $tenantId,
        string $sequenceName,
        string $generatedNumber,
        array $context
    ): void {
        $causerType = null;
        $causerId = null;

        if (auth()->check()) {
            $causer = auth()->user();
            $causerType = get_class($causer);
            $causerId = $causer->id;
        }

        SerialNumberLog::create([
            'tenant_id' => $tenantId,
            'sequence_name' => $sequenceName,
            'generated_number' => $generatedNumber,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'metadata' => $context,
            'created_at' => now(),
        ]);
    }
}
