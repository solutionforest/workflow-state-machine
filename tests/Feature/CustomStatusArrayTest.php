<?php

use Illuminate\Support\Facades\Schema;
use WorkflowStateMachine\Events\WorkflowCreated;
use WorkflowStateMachine\Listeners\CreateWorkflowProcesses;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Services\AutoWorkflowService;
use WorkflowStateMachine\Tests\Models\CustomStatusArrayModel;
use WorkflowStateMachine\Tests\Models\DefaultStatusArrayModel;

beforeEach(function () {
    // Create test table with custom status array model
    if (! Schema::hasTable('custom_status_array_models')) {
        Schema::create('custom_status_array_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('order_status')->nullable();
            $table->timestamps();
        });
    }

    // Create test table with default status array model
    if (! Schema::hasTable('default_status_array_models')) {
        Schema::create('default_status_array_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up
    CustomStatusArrayModel::truncate();
    DefaultStatusArrayModel::truncate();
    Workflow::truncate();
});

it('can get custom status array from model property', function () {
    $model = new CustomStatusArrayModel;

    $statusArray = $model->getStatusArray();

    expect($statusArray)->toBe([
        'pending' => 'Pending Payment',
        'paid' => 'Payment Received',
        'processing' => 'Processing Order',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ]);
});

it('falls back to config status array', function () {
    config(['workflow-state-machine.status' => [
        'draft' => 'Draft',
        'review' => 'Under Review',
        'published' => 'Published',
    ]]);

    $model = new DefaultStatusArrayModel;

    $statusArray = $model->getStatusArray();

    expect($statusArray)->toBe([
        'draft' => 'Draft',
        'review' => 'Under Review',
        'published' => 'Published',
    ]);
});

it('auto-creates workflow with custom status array', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Order Workflow',
    ]);

    $model = CustomStatusArrayModel::create(['name' => 'Test Order']);

    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->not->toBeNull();
    expect($workflow->starting_status)->toBe('pending'); // First status from custom array
    expect($workflow->ending_status)->toBe('cancelled'); // Last status from custom array
    expect($model->fresh()->getCurrentStatus())->toBe('pending');
    expect($model->fresh()->order_status)->toBe('pending');
});

it('auto-creates workflow with default status array', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Default Workflow',
        'workflow-state-machine.status' => [
            'new' => 'New',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
    ]);

    $model = DefaultStatusArrayModel::create(['name' => 'Test Item']);

    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->not->toBeNull();
    expect($workflow->starting_status)->toBe('new'); // First status from config
    expect($workflow->ending_status)->toBe('inactive'); // Last status from config
    expect($model->fresh()->getCurrentStatus())->toBe('new');
    expect($model->fresh()->status)->toBe('new');
});

it('creates workflow processes based on custom status array', function () {
    config([
        'workflow-state-machine.auto_create_processes' => true,
    ]);

    $model = CustomStatusArrayModel::create(['name' => 'Test Order']);

    $workflow = Workflow::create([
        'name' => 'Order Workflow',
        'description' => 'Order processing workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'pending',
        'ending_status' => 'delivered',
    ]);

    // Manually trigger the event since we're testing the listener
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->orderBy('order')->get();

    expect($processes->count())->toBeGreaterThan(0);

    // Should create processes for: pending->paid, paid->processing, processing->shipped, shipped->delivered
    // (excluding cancelled from the flow)
    $firstProcess = $processes->first();
    expect($firstProcess->from_status)->toBe('pending');
    expect($firstProcess->to_status)->toBe('paid');
});

it('creates workflow processes based on default status array', function () {
    config([
        'workflow-state-machine.auto_create_processes' => true,
        'workflow-state-machine.status' => [
            'new' => 'New',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
    ]);

    $model = DefaultStatusArrayModel::create(['name' => 'Test Item']);

    $workflow = Workflow::create([
        'name' => 'Default Workflow',
        'description' => 'Default workflow',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
        'starting_status' => 'new',
        'ending_status' => 'inactive',
    ]);

    // Manually trigger the event
    $listener = new CreateWorkflowProcesses;
    $listener->handle(new WorkflowCreated($workflow));

    $processes = $workflow->processes()->orderBy('order')->get();

    expect($processes->count())->toBe(2); // new->active, active->inactive

    $firstProcess = $processes->first();
    expect($firstProcess->from_status)->toBe('new');
    expect($firstProcess->to_status)->toBe('active');

    $secondProcess = $processes->skip(1)->first();
    expect($secondProcess->from_status)->toBe('active');
    expect($secondProcess->to_status)->toBe('inactive');
});

it('can transition through custom status array', function () {
    $model = CustomStatusArrayModel::create(['name' => 'Test Order', 'order_status' => 'pending']);

    $workflow = Workflow::create([
        'name' => 'Order Workflow',
        'starting_status' => 'pending',
        'ending_status' => 'delivered',
        'workflowable_type' => get_class($model),
        'workflowable_id' => $model->id,
    ]);

    $model->workflows()->save($workflow);

    // Test that model understands its custom status array
    $statusArray = $model->getStatusArray();
    expect($statusArray['pending'])->toBe('Pending Payment');
    expect($statusArray['paid'])->toBe('Payment Received');

    expect($model->getCurrentStatus())->toBe('pending');
});

it('uses hardcoded fallback when no config and no model status array', function () {
    config(['workflow-state-machine.status' => null]);

    $model = new DefaultStatusArrayModel;

    $statusArray = $model->getStatusArray();

    // Should use the hardcoded fallback from the trait
    expect($statusArray)->toHaveKeys(['draft', 'pending', 'in_progress', 'review', 'approved', 'rejected', 'completed', 'cancelled']);
    expect($statusArray['draft'])->toBe('Draft');
    expect($statusArray['completed'])->toBe('Completed');
});
