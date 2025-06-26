<?php

use Illuminate\Support\Facades\Auth;
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

test('it uses current authenticated user when user parameter is not provided', function () {
    // Create a test model
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    // Create a test user
    $user = TestUser::create([
        'name' => 'Authenticated User',
        'email' => 'auth@example.com',
        'role' => 'admin',
    ]);

    // Create a workflow
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for auto user resolution',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Create workflow process
    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Associate workflow with model
    $model->workflows()->save($workflow);

    // Authenticate the user (simulate login)
    Auth::login($user);

    // Call proceedToNextState without passing user parameter
    $result = $model->proceedToNextState(); // No user parameter passed

    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');

    // Check audit log to verify the correct user was recorded
    $auditLog = $model->workflowAuditLogs()->latest()->first();
    expect($auditLog->user_id)->toBe($user->id);
    expect($auditLog->user_type)->toBe(TestUser::class);
});

test('it prefers explicit user parameter over authenticated user', function () {
    // Create a test model
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    // Create two test users
    $authenticatedUser = TestUser::create([
        'name' => 'Authenticated User',
        'email' => 'auth@example.com',
        'role' => 'admin',
    ]);

    $explicitUser = TestUser::create([
        'name' => 'Explicit User',
        'email' => 'explicit@example.com',
        'role' => 'admin',
    ]);

    // Create a workflow
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for user preference',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Create workflow process
    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Associate workflow with model
    $model->workflows()->save($workflow);

    // Authenticate one user
    Auth::login($authenticatedUser);

    // Call proceedToNextState with explicit user parameter
    $result = $model->proceedToNextState($explicitUser);

    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');

    // Check audit log to verify the explicit user was used (not the authenticated one)
    $auditLog = $model->workflowAuditLogs()->latest()->first();
    expect($auditLog->user_id)->toBe($explicitUser->id);
    expect($auditLog->user_type)->toBe(TestUser::class);
});

test('it works without authenticated user when no user parameter provided', function () {
    // Create a test model
    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    // Create a workflow
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow without user',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Create workflow process
    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => false,
        'completed' => false,
    ]);

    // Associate workflow with model
    $model->workflows()->save($workflow);

    // Make sure no user is authenticated
    Auth::logout();

    // Call proceedToNextState without user parameter
    $result = $model->proceedToNextState();

    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');

    // Check audit log to verify no user was recorded
    $auditLog = $model->workflowAuditLogs()->latest()->first();
    expect($auditLog->user_id)->toBeNull();
    expect($auditLog->user_type)->toBeNull();
});
