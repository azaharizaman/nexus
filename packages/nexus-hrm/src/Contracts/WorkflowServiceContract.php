<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

interface WorkflowServiceContract
{
    /** Submit a business object for approval and return a workflow instance id. */
    public function submit(string $type, array $payload): ?string;

    /** Query approval status for a workflow instance. */
    public function status(string $instanceId): ?string;
}
