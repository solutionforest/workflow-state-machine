<?php

use Illuminate\Support\Facades\Schema;
use Tests\Models\TestModel;
use WorkflowStateMachine\Events\WorkflowCreated;
use WorkflowStateMachine\Listeners\CreateWorkflowProcesses;
use WorkflowStateMachine\Models\Workflow;

// Test model with auto-transition enabled
class TestModelWithAutoTransitionEnabled extends TestModel
{
    protected $enable_auto_transition = true;

    protected $table = 'test_models';
}

// Test model with auto-transition disabled
class TestModelWithAutoTransitionDisabled extends TestModel
{
    protected $enable_auto_transition = false;

    protected $table = 'test_models';
}

// Test model without auto-transition setting (uses config fallback)
class TestModelWithoutAutoTransitionSetting extends TestModel
{
    protected $table = 'test_models';
}

beforeEach(function () {
    // Create test tables
    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('status')->default('draft');
        $table->timestamps();
    });

    // Enable auto-create processes for tests
    config(['workflow-state-machine.auto_create_processes' => true]);
});

test('auto-generated workflow processes respect model-level auto-transition enabled setting', function () {
    $model = TestModelWithAutoTransitionEnabled::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->get();

    expect($processes->count())->toBeGreaterThan(0);

    // All processes should have auto_transition = true since model has it enabled
    foreach ($processes as $process) {
        expect($process->auto_transition)->toBe(true);
    }
});

test('auto-generated workflow processes respect model-level auto-transition disabled setting', function () {
    $model = TestModelWithAutoTransitionDisabled::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->get();

    expect($processes->count())->toBeGreaterThan(0);

    // All processes should have auto_transition = false since model has it disabled
    foreach ($processes as $process) {
        expect($process->auto_transition)->toBe(false);
    }
});

test('auto-generated workflow processes fallback to global config when model has no auto-transition setting', function () {
    // Set global config to enabled
    config(['workflow-state-machine.enable_auto_transition' => true]);

    $model = TestModelWithoutAutoTransitionSetting::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->get();

    expect($processes->count())->toBeGreaterThan(0);

    // All processes should have auto_transition = true due to global config
    foreach ($processes as $process) {
        expect($process->auto_transition)->toBe(true);
    }
});

test('auto-generated workflow processes fallback to global config disabled when model has no auto-transition setting', function () {
    // Set global config to disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);

    $model = TestModelWithoutAutoTransitionSetting::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->get();

    expect($processes->count())->toBeGreaterThan(0);

    // All processes should have auto_transition = false due to global config
    foreach ($processes as $process) {
        expect($process->auto_transition)->toBe(false);
    }
});

test('model-level auto-transition setting takes precedence over global config in auto-generated processes', function () {
    // Set global config to disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);

    // But model has auto-transition enabled
    $model = TestModelWithAutoTransitionEnabled::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->get();

    expect($processes->count())->toBeGreaterThan(0);

    // All processes should have auto_transition = true despite global config being false
    // because model-level setting takes precedence
    foreach ($processes as $process) {
        expect($process->auto_transition)->toBe(true);
    }
});
