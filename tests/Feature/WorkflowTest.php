<?php

use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;

test('can create a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'description' => 'Test workflow description',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    expect($workflow)->toBeInstanceOf(Workflow::class)
        ->and($workflow->name)->toBe('test_workflow')
        ->and($workflow->starting_status)->toBe('draft')
        ->and($workflow->ending_status)->toBe('completed');
});

test('can create workflow processes', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'submit_for_review',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => true,
    ]);

    expect($process)->toBeInstanceOf(WorkflowProcess::class)
        ->and($process->workflow_id)->toBe($workflow->id)
        ->and($process->from_status)->toBe('draft')
        ->and($process->to_status)->toBe('pending')
        ->and($process->auto_transition)->toBeTrue();
});

test('workflow can get status roadmap', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'submit',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'approve',
        'from_status' => 'pending',
        'to_status' => 'approved',
        'order' => 2,
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'complete',
        'from_status' => 'approved',
        'to_status' => 'completed',
        'order' => 3,
    ]);

    $roadmap = $workflow->getStatusRoadmap();

    expect($roadmap)->toBe(['draft', 'pending', 'approved', 'completed']);
});

test('workflow can get next and previous status', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'submit',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'approve',
        'from_status' => 'pending',
        'to_status' => 'approved',
        'order' => 2,
    ]);

    expect($workflow->getNextStatus('draft'))->toBe('pending')
        ->and($workflow->getNextStatus('pending'))->toBe('approved')
        ->and($workflow->getNextStatus('approved'))->toBeNull()
        ->and($workflow->getPreviousStatus('approved'))->toBe('pending')
        ->and($workflow->getPreviousStatus('pending'))->toBe('draft')
        ->and($workflow->getPreviousStatus('draft'))->toBeNull();
});
