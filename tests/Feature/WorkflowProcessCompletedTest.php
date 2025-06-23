<?php

use Illuminate\Support\Facades\Schema;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Tests\Models\AutoWorkflowTestModel;

it('can create workflow process with completed field', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for completed field',
        'workflowable_type' => 'App\\Models\\Test',
        'workflowable_id' => 1,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'test_process',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
        'description' => 'Test process with completed field',
    ]);

    expect($process)->toBeInstanceOf(WorkflowProcess::class)
        ->and($process->completed)->toBeFalse()
        ->and($process->workflow_id)->toBe($workflow->id);
});

it('can update workflow process completed status', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for completed status update',
        'workflowable_type' => 'App\\Models\\Test',
        'workflowable_id' => 1,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'test_process',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Update completed status
    $process->update(['completed' => true]);

    expect($process->fresh()->completed)->toBeTrue();
});

it('defaults completed to false when not specified', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for default completed value',
        'workflowable_type' => 'App\\Models\\Test',
        'workflowable_id' => 1,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'test_process',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        // completed field not specified, should default to false
    ]);

    expect($process->completed)->toBeFalse();
});

it('marks workflow process as completed when status transitions', function () {
    // Create test table if it doesn't exist
    if (! Schema::hasTable('auto_workflow_test_models')) {
        Schema::create('auto_workflow_test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for transition completion',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'submit_process',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Create a test model
    $model = AutoWorkflowTestModel::create([
        'name' => 'Test Model',
        'status' => 'draft',
    ]);

    // Associate the workflow with the model
    $workflow->workflowable()->associate($model);
    $workflow->save();

    // Initially the process should not be completed
    expect($process->completed)->toBeFalse();

    // Perform the transition
    $result = $model->transitionTo('pending');

    // The transition should succeed
    expect($result)->toBeTrue();

    // The process should now be marked as completed
    expect($process->fresh()->completed)->toBeTrue();

    // Clean up
    AutoWorkflowTestModel::truncate();
    Schema::dropIfExists('auto_workflow_test_models');
});
