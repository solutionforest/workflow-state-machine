<?php

use Illuminate\Support\Facades\Schema;
use Tests\Models\TestModel;
use Tests\Models\TestUser;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;

beforeEach(function () {
    // Create test tables
    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('status')->default('draft');
        $table->unsignedBigInteger('assignee_id')->nullable();
        $table->timestamps();
    });

    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('role')->default('user');
        $table->timestamps();
    });
});

test('model can proceed to next state', function () {
    // Create a test model
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    // Create a test user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin',
    ]);

    // Create a workflow
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for proceed to next state',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Create workflow processes
    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'pending_to_approved',
        'from_status' => 'pending',
        'to_status' => 'approved',
        'order' => 2,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Associate workflow with model
    $model->workflows()->save($workflow);

    // Test proceeding to next state
    $result = $model->proceedToNextState($user, 'Moving to next step');

    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');

    // Test proceeding again
    $result = $model->proceedToNextState($user, 'Moving to approved');

    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('approved');
});

test('model cannot proceed when at final state', function () {
    // Create a test model at final state
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'completed', // Already at final state
    ]);

    // Create a test user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin',
    ]);

    // Create a workflow
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Associate workflow with model
    $model->workflows()->save($workflow);

    // Try to proceed (should fail)
    $result = $model->proceedToNextState($user);

    expect($result)->toBeFalse();
    expect($model->getCurrentStatus())->toBe('completed');
});

test('model cannot proceed when no workflow exists', function () {
    // Create a test model without workflow
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    // Create a test user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin',
    ]);

    // Try to proceed (should fail)
    $result = $model->proceedToNextState($user);

    expect($result)->toBeFalse();
    expect($model->getCurrentStatus())->toBe('draft');
});
