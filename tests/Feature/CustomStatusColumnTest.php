<?php

use Illuminate\Support\Facades\Schema;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Services\AutoWorkflowService;
use WorkflowStateMachine\Tests\Models\CustomStatusColumnModel;
use WorkflowStateMachine\Tests\Models\DefaultStatusColumnModel;

beforeEach(function () {
    // Create test table with custom status column
    if (! Schema::hasTable('custom_status_models')) {
        Schema::create('custom_status_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('custom_status')->nullable();
            $table->timestamps();
        });
    }

    // Create test table with default status column
    if (! Schema::hasTable('default_status_models')) {
        Schema::create('default_status_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up
    CustomStatusColumnModel::truncate();
    DefaultStatusColumnModel::truncate();
    Workflow::truncate();
});

it('can get custom status column name from model property', function () {
    $model = new CustomStatusColumnModel;

    expect($model->getStatusColumnName())->toBe('custom_status');
});

it('falls back to config default status column name', function () {
    config(['workflow-state-machine.status_column' => 'status']);

    $model = new DefaultStatusColumnModel;

    expect($model->getStatusColumnName())->toBe('status');
});

it('falls back to hardcoded default when config is missing', function () {
    config(['workflow-state-machine.status_column' => null]);

    $model = new DefaultStatusColumnModel;

    expect($model->getStatusColumnName())->toBe('status');
});

it('can get and set current status using custom column', function () {
    $model = CustomStatusColumnModel::create(['name' => 'Test Model']);

    expect($model->getCurrentStatus())->toBeNull();

    $model->setStatus('draft');
    $model->save();

    expect($model->getCurrentStatus())->toBe('draft');
    expect($model->custom_status)->toBe('draft');
});

it('can get and set current status using default column', function () {
    $model = DefaultStatusColumnModel::create(['name' => 'Test Model']);

    expect($model->getCurrentStatus())->toBeNull();

    $model->setStatus('pending');
    $model->save();

    expect($model->getCurrentStatus())->toBe('pending');
    expect($model->status)->toBe('pending');
});

it('can transition between statuses using custom column', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'Draft to Pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
    ]);

    $model = CustomStatusColumnModel::create(['name' => 'Test Model', 'custom_status' => 'draft']);
    $model->workflows()->save($workflow);

    expect($model->canTransitionTo('pending'))->toBeTrue();
    expect($model->transitionTo('pending'))->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');
});

it('auto-creates workflow with custom status column', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Custom Column Workflow',
    ]);

    $model = CustomStatusColumnModel::create(['name' => 'Test Model']);

    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->not->toBeNull();
    expect($model->fresh()->getCurrentStatus())->toBe('draft');
    expect($model->fresh()->custom_status)->toBe('draft');
});

it('auto-creates workflow with default status column', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Default Column Workflow',
    ]);

    $model = DefaultStatusColumnModel::create(['name' => 'Test Model']);

    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->not->toBeNull();
    expect($model->fresh()->getCurrentStatus())->toBe('draft');
    expect($model->fresh()->status)->toBe('draft');
});

it('gets workflow progress with custom status column', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $model = CustomStatusColumnModel::create(['name' => 'Test Model', 'custom_status' => 'pending']);
    $model->workflows()->save($workflow);

    $progress = $model->getWorkflowProgress();

    expect($progress['current_status'])->toBe('pending');
});

it('can use custom status column name from config', function () {
    config(['workflow-state-machine.status_column' => 'state']);

    $model = new DefaultStatusColumnModel;

    expect($model->getStatusColumnName())->toBe('state');
});
