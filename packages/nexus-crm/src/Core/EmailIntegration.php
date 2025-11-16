<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Support\Facades\Log;
use Nexus\Crm\Contracts\IntegrationContract;
use Nexus\Crm\Models\CrmEntity;

/**
 * Email Integration
 *
 * Sends emails as part of CRM pipeline actions.
 */
class EmailIntegration implements IntegrationContract
{
    /**
     * Execute email integration.
     */
    public function execute(CrmEntity $entity, array $config, array $context = []): void
    {
        $to = $config['to'] ?? [];
        $subject = $config['subject'] ?? 'CRM Update';
        $template = $config['template'] ?? 'crm-update';

        // In a real implementation, this would use Laravel's Mail facade
        // For now, just log the email
        Log::info('CRM Email Integration', [
            'entity_id' => $entity->id,
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
        ]);
    }

    /**
     * Compensate email integration (no-op for emails).
     */
    public function compensate(CrmEntity $entity, array $config, array $context = []): void
    {
        // Emails don't need compensation
    }
}