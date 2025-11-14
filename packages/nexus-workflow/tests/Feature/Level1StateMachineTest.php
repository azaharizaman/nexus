<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Nexus\Workflow\Tests\Support\Post;

beforeEach(function () {
    // Create posts table for testing
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('workflow_state')->default('draft');
        $table->unsignedBigInteger('author_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('posts');
});

describe('HasWorkflow trait', function () {
    it('initializes workflow state on model creation', function () {
        $post = new Post(['title' => 'Test Post']);
        
        expect($post->getWorkflowState())->toBe('draft');
    });
    
    it('provides workflow manager via workflow() method', function () {
        $post = new Post(['title' => 'Test Post']);
        
        expect($post->workflow())
            ->toBeInstanceOf(\Nexus\Workflow\Adapters\Laravel\Services\WorkflowManager::class);
    });
    
    it('returns workflow definition', function () {
        $post = new Post(['title' => 'Test Post']);
        $definition = $post->getWorkflowDefinition();
        
        expect($definition)
            ->toBeInstanceOf(\Nexus\Workflow\Core\DTOs\WorkflowDefinition::class)
            ->and($definition->initialState)->toBe('draft')
            ->and($definition->states)->toHaveKey('draft', 'in_review', 'published', 'archived');
    });
});

describe('State transitions', function () {
    it('can check if transition is allowed', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        expect($post->workflow()->can('submit'))->toBeTrue()
            ->and($post->workflow()->can('approve'))->toBeFalse();
    });
    
    it('can apply valid transitions', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        $result = $post->workflow()->apply('submit');
        
        expect($result->isSuccess())->toBeTrue()
            ->and($result->fromState)->toBe('draft')
            ->and($result->toState)->toBe('in_review')
            ->and($post->getWorkflowState())->toBe('in_review');
        
        // Verify database persistence
        $post->refresh();
        expect($post->getWorkflowState())->toBe('in_review');
    });
    
    it('prevents invalid transitions', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        // Try to approve from draft (not allowed)
        $result = $post->workflow()->apply('approve', ['can_publish' => true]);
        
        expect($result->isFailure())->toBeTrue()
            ->and($post->getWorkflowState())->toBe('draft');
    });
    
    it('can chain multiple transitions', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        // draft -> in_review -> published
        $post->workflow()->apply('submit');
        expect($post->getWorkflowState())->toBe('in_review');
        
        $post->workflow()->apply('approve', ['can_publish' => true]);
        expect($post->getWorkflowState())->toBe('published');
        
        // Verify persistence
        $post->refresh();
        expect($post->getWorkflowState())->toBe('published');
    });
});

describe('Guard conditions', function () {
    it('respects guard conditions', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => null, // Missing author
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        expect($post->getWorkflowState())->toBe('in_review');
        
        // Try to approve without author (guard should fail)
        expect($post->workflow()->can('approve', ['can_publish' => true]))
            ->toBeFalse();
        
        // Add author
        $post->author_id = 1;
        $post->save();
        
        // Now approval should be allowed
        expect($post->workflow()->can('approve', ['can_publish' => true]))
            ->toBeTrue();
    });
    
    it('evaluates context in guard conditions', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        
        // Without can_publish permission
        expect($post->workflow()->can('approve', ['can_publish' => false]))
            ->toBeFalse();
        
        // With can_publish permission
        expect($post->workflow()->can('approve', ['can_publish' => true]))
            ->toBeTrue();
    });
});

describe('Transition hooks', function () {
    it('executes after hooks', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        $post->workflow()->apply('approve', ['can_publish' => true]);
        
        // Check that the after hook was executed
        expect($post->notification_sent)->toBeTrue();
    });
});

describe('History tracking', function () {
    it('tracks transition history', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        $post->workflow()->apply('submit');
        
        $history = $post->workflow()->history();
        
        expect($history)->toHaveCount(1)
            ->and($history[0])
            ->toHaveKey('transition', 'submit')
            ->toHaveKey('from', 'draft')
            ->toHaveKey('to', 'in_review')
            ->toHaveKey('timestamp');
    });
    
    it('tracks multiple transitions', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        $post->workflow()->apply('approve', ['can_publish' => true]);
        $post->workflow()->apply('archive');
        
        $history = $post->workflow()->history();
        
        expect($history)->toHaveCount(3)
            ->and($history[0]['transition'])->toBe('submit')
            ->and($history[1]['transition'])->toBe('approve')
            ->and($history[2]['transition'])->toBe('archive');
    });
    
    it('includes context metadata in history', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        $post->workflow()->apply('approve', [
            'can_publish' => true,
            'approved_by' => 42,
            'comment' => 'Looks good!',
        ]);
        
        $history = $post->workflow()->history();
        $approvalHistory = $history[1];
        
        expect($approvalHistory['metadata'])
            ->toHaveKey('can_publish', true)
            ->toHaveKey('approved_by', 42)
            ->toHaveKey('comment', 'Looks good!');
    });
});

describe('Available transitions', function () {
    it('returns available transitions from current state', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        $transitions = $post->workflow()->availableTransitions();
        
        expect($transitions)->toBe(['submit']);
    });
    
    it('returns multiple transitions when applicable', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        
        // From in_review, can either approve or reject
        $transitions = $post->workflow()->availableTransitions(['can_publish' => true]);
        
        expect($transitions)->toContain('approve', 'reject');
    });
    
    it('respects guard conditions in available transitions', function () {
        $post = new Post([
            'title' => 'Test Post',
            'author_id' => 1,
        ]);
        $post->save();
        
        $post->workflow()->apply('submit');
        
        // Without permission, approve should not be available
        $transitions = $post->workflow()->availableTransitions(['can_publish' => false]);
        expect($transitions)->not->toContain('approve');
        expect($transitions)->toContain('reject');
        
        // With permission, approve should be available
        $transitions = $post->workflow()->availableTransitions(['can_publish' => true]);
        expect($transitions)->toContain('approve', 'reject');
    });
});

describe('State checking', function () {
    it('can check current state', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        expect($post->workflow()->currentState())->toBe('draft');
        expect($post->workflow()->isInState('draft'))->toBeTrue();
        expect($post->workflow()->isInState('published'))->toBeFalse();
    });
    
    it('updates state check after transition', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        $post->workflow()->apply('submit');
        
        expect($post->workflow()->currentState())->toBe('in_review');
        expect($post->workflow()->isInState('in_review'))->toBeTrue();
        expect($post->workflow()->isInState('draft'))->toBeFalse();
    });
});

describe('ACID compliance', function () {
    it('wraps state changes in database transaction', function () {
        $post = new Post(['title' => 'Test Post']);
        $post->save();
        
        $initialState = $post->getWorkflowState();
        
        try {
            // Apply transition that will fail due to guard
            $post->workflow()->apply('approve', ['can_publish' => true]);
        } catch (\Exception $e) {
            // Even if an exception occurred, state should be consistent
        }
        
        // Verify state consistency
        $post->refresh();
        expect($post->getWorkflowState())->toBe($initialState);
    });
});

describe('Real-world blog post workflow', function () {
    it('completes the full blog post lifecycle', function () {
        // Author creates a draft
        $post = new Post([
            'title' => 'My Awesome Blog Post',
            'content' => 'This is the content...',
            'author_id' => 1,
        ]);
        $post->save();
        
        expect($post->workflow()->currentState())->toBe('draft');
        
        // Author submits for review
        $result = $post->workflow()->apply('submit');
        expect($result->isSuccess())->toBeTrue();
        expect($post->workflow()->currentState())->toBe('in_review');
        
        // Editor approves
        $result = $post->workflow()->apply('approve', [
            'can_publish' => true,
            'approved_by' => 2,
        ]);
        expect($result->isSuccess())->toBeTrue();
        expect($post->workflow()->currentState())->toBe('published');
        expect($post->notification_sent)->toBeTrue();
        
        // Later, post is archived
        $result = $post->workflow()->apply('archive');
        expect($result->isSuccess())->toBeTrue();
        expect($post->workflow()->currentState())->toBe('archived');
        
        // Can be restored if needed
        $result = $post->workflow()->apply('restore');
        expect($result->isSuccess())->toBeTrue();
        expect($post->workflow()->currentState())->toBe('published');
        
        // Verify complete history
        $history = $post->workflow()->history();
        expect($history)->toHaveCount(4)
            ->and($history[0]['transition'])->toBe('submit')
            ->and($history[1]['transition'])->toBe('approve')
            ->and($history[2]['transition'])->toBe('archive')
            ->and($history[3]['transition'])->toBe('restore');
    });
});
