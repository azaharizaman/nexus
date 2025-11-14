<?php

declare(strict_types=1);

use Nexus\Workflow\Core\DTOs\TransitionResult;
use Nexus\Workflow\Core\DTOs\WorkflowDefinition;
use Nexus\Workflow\Core\DTOs\WorkflowInstance;
use Nexus\Workflow\Core\Services\StateTransitionService;

describe('StateTransitionService', function () {
    beforeEach(function () {
        $this->service = new StateTransitionService();
        
        // Standard workflow definition for testing
        $this->definition = WorkflowDefinition::fromArray([
            'id' => 'test-workflow',
            'initialState' => 'draft',
            'states' => [
                'draft' => ['label' => 'Draft'],
                'review' => ['label' => 'In Review'],
                'published' => ['label' => 'Published'],
                'archived' => ['label' => 'Archived'],
            ],
            'transitions' => [
                'submit' => [
                    'from' => ['draft'],
                    'to' => 'review',
                    'label' => 'Submit for Review',
                ],
                'approve' => [
                    'from' => ['review'],
                    'to' => 'published',
                    'label' => 'Approve',
                ],
                'reject' => [
                    'from' => ['review'],
                    'to' => 'draft',
                    'label' => 'Reject',
                ],
                'archive' => [
                    'from' => ['published'],
                    'to' => 'archived',
                    'label' => 'Archive',
                ],
            ],
        ]);
        
        $this->instance = new WorkflowInstance(
            definition: $this->definition,
            currentState: 'draft',
        );
    });
    
    describe('canTransition()', function () {
        it('returns true for valid transitions', function () {
            expect($this->service->canTransition($this->instance, 'submit'))
                ->toBeTrue();
        });
        
        it('returns false for invalid transitions', function () {
            expect($this->service->canTransition($this->instance, 'approve'))
                ->toBeFalse();
        });
        
        it('returns false for non-existent transitions', function () {
            expect($this->service->canTransition($this->instance, 'nonexistent'))
                ->toBeFalse();
        });
        
        it('respects guard conditions', function () {
            $definition = WorkflowDefinition::fromArray([
                'id' => 'guarded-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                        'guard' => function ($subject, $context) {
                            return isset($context['can_publish']) && $context['can_publish'] === true;
                        },
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            // Without permission
            expect($this->service->canTransition($instance, 'publish', ['can_publish' => false]))
                ->toBeFalse();
            
            // With permission
            expect($this->service->canTransition($instance, 'publish', ['can_publish' => true]))
                ->toBeTrue();
        });
    });
    
    describe('applyTransition()', function () {
        it('successfully applies valid transitions', function () {
            $result = $this->service->applyTransition($this->instance, 'submit');
            
            expect($result)
                ->toBeInstanceOf(TransitionResult::class)
                ->and($result->isSuccess())->toBeTrue()
                ->and($result->fromState)->toBe('draft')
                ->and($result->toState)->toBe('review')
                ->and($result->transitionName)->toBe('submit')
                ->and($this->instance->getCurrentState())->toBe('review');
        });
        
        it('updates instance state after successful transition', function () {
            $this->service->applyTransition($this->instance, 'submit');
            
            expect($this->instance->getCurrentState())->toBe('review');
        });
        
        it('throws exception for non-existent transitions', function () {
            expect(fn () => $this->service->applyTransition($this->instance, 'nonexistent'))
                ->toThrow(\InvalidArgumentException::class, "Transition 'nonexistent' does not exist");
        });
        
        it('returns failure for invalid transitions', function () {
            $result = $this->service->applyTransition($this->instance, 'approve');
            
            expect($result->isFailure())->toBeTrue()
                ->and($result->fromState)->toBe('draft')
                ->and($result->toState)->toBe('draft') // State unchanged
                ->and($this->instance->getCurrentState())->toBe('draft');
        });
        
        it('throws exception for invalid target state', function () {
            $definition = WorkflowDefinition::fromArray([
                'id' => 'invalid-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                ],
                'transitions' => [
                    'submit' => [
                        'from' => ['draft'],
                        'to' => 'nonexistent_state',
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            expect(fn () => $this->service->applyTransition($instance, 'submit'))
                ->toThrow(\InvalidArgumentException::class, "Target state 'nonexistent_state' does not exist");
        });
    });
    
    describe('transition hooks', function () {
        it('executes before hook', function () {
            $hookCalled = false;
            
            $definition = WorkflowDefinition::fromArray([
                'id' => 'hook-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                        'before' => function ($subject, $context) use (&$hookCalled) {
                            $hookCalled = true;
                        },
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            $this->service->applyTransition($instance, 'publish');
            
            expect($hookCalled)->toBeTrue();
        });
        
        it('executes after hook', function () {
            $hookCalled = false;
            
            $definition = WorkflowDefinition::fromArray([
                'id' => 'hook-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                        'after' => function ($subject, $context) use (&$hookCalled) {
                            $hookCalled = true;
                        },
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            $this->service->applyTransition($instance, 'publish');
            
            expect($hookCalled)->toBeTrue();
        });
        
        it('passes correct context to hooks', function () {
            $capturedContext = null;
            
            $definition = WorkflowDefinition::fromArray([
                'id' => 'hook-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                        'before' => function ($subject, $context) use (&$capturedContext) {
                            $capturedContext = $context;
                        },
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            $this->service->applyTransition($instance, 'publish', ['user_id' => 123]);
            
            expect($capturedContext)
                ->toHaveKey('from', 'draft')
                ->toHaveKey('to', 'published')
                ->toHaveKey('instance')
                ->toHaveKey('user_id', 123);
        });
    });
    
    describe('history tracking', function () {
        it('records transitions in history', function () {
            $this->service->applyTransition($this->instance, 'submit');
            
            $history = $this->instance->getHistory();
            
            expect($history)->toHaveCount(1)
                ->and($history[0])
                ->toHaveKey('transition', 'submit')
                ->toHaveKey('from', 'draft')
                ->toHaveKey('to', 'review')
                ->toHaveKey('timestamp');
        });
        
        it('records multiple transitions', function () {
            $this->service->applyTransition($this->instance, 'submit');
            $this->service->applyTransition($this->instance, 'approve');
            
            $history = $this->instance->getHistory();
            
            expect($history)->toHaveCount(2)
                ->and($history[0]['transition'])->toBe('submit')
                ->and($history[1]['transition'])->toBe('approve');
        });
        
        it('includes metadata in history', function () {
            $this->service->applyTransition($this->instance, 'submit', [
                'user_id' => 123,
                'comment' => 'Ready for review',
            ]);
            
            $history = $this->instance->getHistory();
            
            expect($history[0]['metadata'])
                ->toHaveKey('user_id', 123)
                ->toHaveKey('comment', 'Ready for review');
        });
    });
    
    describe('getAvailableTransitions()', function () {
        it('returns all valid transitions from current state', function () {
            $transitions = $this->service->getAvailableTransitions($this->instance);
            
            expect($transitions)->toBe(['submit']);
        });
        
        it('returns multiple transitions when applicable', function () {
            $this->instance->setCurrentState('review');
            
            $transitions = $this->service->getAvailableTransitions($this->instance);
            
            expect($transitions)->toContain('approve', 'reject');
        });
        
        it('returns empty array when no transitions available', function () {
            $this->instance->setCurrentState('archived');
            
            $transitions = $this->service->getAvailableTransitions($this->instance);
            
            expect($transitions)->toBeEmpty();
        });
        
        it('respects guard conditions', function () {
            $definition = WorkflowDefinition::fromArray([
                'id' => 'guarded-workflow',
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                        'guard' => fn ($subject, $context) => $context['can_publish'] ?? false,
                    ],
                ],
            ]);
            
            $instance = new WorkflowInstance(
                definition: $definition,
                currentState: 'draft',
            );
            
            // Without permission
            $transitions = $this->service->getAvailableTransitions($instance, ['can_publish' => false]);
            expect($transitions)->toBeEmpty();
            
            // With permission
            $transitions = $this->service->getAvailableTransitions($instance, ['can_publish' => true]);
            expect($transitions)->toBe(['publish']);
        });
    });
    
    describe('validateDefinition()', function () {
        it('validates correct definitions', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => [
                    'draft' => ['label' => 'Draft'],
                    'published' => ['label' => 'Published'],
                ],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'published',
                    ],
                ],
            ];
            
            expect($this->service->validateDefinition($definition))->toBeTrue();
        });
        
        it('throws exception for missing initialState', function () {
            $definition = [
                'states' => ['draft' => []],
                'transitions' => [],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, 'must have an initialState');
        });
        
        it('throws exception for missing states', function () {
            $definition = [
                'initialState' => 'draft',
                'transitions' => [],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, 'must have a states array');
        });
        
        it('throws exception for missing transitions', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => ['draft' => []],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, 'must have a transitions array');
        });
        
        it('throws exception for invalid initialState', function () {
            $definition = [
                'initialState' => 'nonexistent',
                'states' => ['draft' => []],
                'transitions' => [],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, "Initial state 'nonexistent' does not exist");
        });
        
        it('throws exception for transition with missing from', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => ['draft' => [], 'published' => []],
                'transitions' => [
                    'publish' => ['to' => 'published'],
                ],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, "must have a 'from' field");
        });
        
        it('throws exception for transition with missing to', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => ['draft' => [], 'published' => []],
                'transitions' => [
                    'publish' => ['from' => ['draft']],
                ],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, "must have a 'to' field");
        });
        
        it('throws exception for transition with invalid from state', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => ['draft' => [], 'published' => []],
                'transitions' => [
                    'publish' => [
                        'from' => ['nonexistent'],
                        'to' => 'published',
                    ],
                ],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, "non-existent 'from' state: 'nonexistent'");
        });
        
        it('throws exception for transition with invalid to state', function () {
            $definition = [
                'initialState' => 'draft',
                'states' => ['draft' => [], 'published' => []],
                'transitions' => [
                    'publish' => [
                        'from' => ['draft'],
                        'to' => 'nonexistent',
                    ],
                ],
            ];
            
            expect(fn () => $this->service->validateDefinition($definition))
                ->toThrow(\InvalidArgumentException::class, "non-existent 'to' state: 'nonexistent'");
        });
    });
    
    describe('utility methods', function () {
        it('getCurrentState() returns current state', function () {
            expect($this->service->getCurrentState($this->instance))->toBe('draft');
            
            $this->instance->setCurrentState('review');
            expect($this->service->getCurrentState($this->instance))->toBe('review');
        });
        
        it('stateExists() checks state existence', function () {
            expect($this->service->stateExists($this->instance, 'draft'))->toBeTrue();
            expect($this->service->stateExists($this->instance, 'nonexistent'))->toBeFalse();
        });
    });
});
