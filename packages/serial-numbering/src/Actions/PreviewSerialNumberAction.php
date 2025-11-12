<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Actions;

use Azaharizaman\Erp\SerialNumbering\Contracts\PatternParserContract;
use Azaharizaman\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Preview Serial Number Action
 *
 * Generates a preview of the next serial number without consuming
 * the counter.
 */
class PreviewSerialNumberAction
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
     * @return string The preview serial number
     *
     * @throws SequenceNotFoundException
     */
    public function handle(string $tenantId, string $sequenceName, array $context = []): string
    {
        // Find sequence configuration
        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        // Use next counter value (current + 1) for preview
        $nextCounter = $sequence->current_value + 1;

        // Build context for pattern evaluation
        $patternContext = array_merge([
            'counter' => $nextCounter,
            'padding' => $sequence->padding,
            'tenant_code' => $context['tenant_code'] ?? '',
            'prefix' => $context['prefix'] ?? '',
            'department_code' => $context['department_code'] ?? '',
        ], $context);

        // Parse pattern to generate preview
        return $this->parser->preview($sequence->pattern, $patternContext);
    }
}
