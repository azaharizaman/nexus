<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Nexus\Workflow\Adapters\Laravel\Traits\HasWorkflow;

/**
 * Test model for demonstrating Level 1 workflow functionality
 */
class Post extends Model
{
    use HasWorkflow;

    protected $fillable = ['title', 'content', 'workflow_state', 'author_id'];

    /**
     * Define the workflow for blog posts
     */
    public function workflowDefinition(): array
    {
        return [
            'initialState' => 'draft',
            'label' => 'Blog Post Workflow',
            'version' => '1.0.0',
            
            'states' => [
                'draft' => [
                    'label' => 'Draft',
                ],
                'in_review' => [
                    'label' => 'In Review',
                ],
                'published' => [
                    'label' => 'Published',
                ],
                'archived' => [
                    'label' => 'Archived',
                ],
            ],
            
            'transitions' => [
                'submit' => [
                    'label' => 'Submit for Review',
                    'from' => ['draft'],
                    'to' => 'in_review',
                ],
                
                'approve' => [
                    'label' => 'Approve and Publish',
                    'from' => ['in_review'],
                    'to' => 'published',
                    'guard' => function ($post, $context) {
                        // Only allow approval if author is set and user can publish
                        return $post->author_id !== null && ($context['can_publish'] ?? false);
                    },
                    'after' => function ($post, $context) {
                        // Simulate notification
                        $post->notification_sent = true;
                    },
                ],
                
                'reject' => [
                    'label' => 'Request Changes',
                    'from' => ['in_review'],
                    'to' => 'draft',
                ],
                
                'archive' => [
                    'label' => 'Archive',
                    'from' => ['published'],
                    'to' => 'archived',
                ],
                
                'restore' => [
                    'label' => 'Restore from Archive',
                    'from' => ['archived'],
                    'to' => 'published',
                ],
            ],
        ];
    }

    // Test property to track after hook execution
    public $notification_sent = false;
}
