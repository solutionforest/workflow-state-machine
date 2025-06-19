<?php

use Illuminate\Support\Facades\Schema;
use Tests\Models\TestModel;
use Tests\Models\TestUser;
use Tests\Rules\TestUserPermissionRule;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Models\WorkflowRule;

beforeEach(function () {
    // Create test table
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

test('model can associate with workflow', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $model = TestModel::create(['title' => 'Test Task']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    expect($model->workflow)->toBeInstanceOf(Workflow::class)
        ->and($model->workflow->name)->toBe('test_workflow');
});

test('model can transition between statuses', function () {
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

    $model = TestModel::create(['title' => 'Test Task', 'status' => 'draft']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    expect($model->canTransitionTo('pending'))->toBeTrue();

    $result = $model->transitionTo('pending');

    expect($result)->toBeTrue()
        ->and($model->fresh()->status)->toBe('pending');
});

test('model respects user permissions', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $process = WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'approve',
        'from_status' => 'pending',
        'to_status' => 'approved',
        'order' => 1,
    ]);

    $rule = WorkflowRule::create([
        'name' => 'user_permission_rule',
        'rule_class' => TestUserPermissionRule::class,
    ]);

    $process->rules()->attach($rule);

    $model = TestModel::create(['title' => 'Test Task', 'status' => 'pending']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    $user = TestUser::create(['name' => 'Test User', 'email' => 'test@example.com', 'role' => 'user']);
    $admin = TestUser::create(['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin']);

    expect($model->canTransitionTo('approved', $user))->toBeFalse()
        ->and($model->canTransitionTo('approved', $admin))->toBeTrue();
});

test('model logs status changes', function () {
    config(['workflow-state-machine.enable_audit_log' => true]);

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

    $model = TestModel::create(['title' => 'Test Task', 'status' => 'draft']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    $user = TestUser::create(['name' => 'Test User', 'email' => 'test@example.com']);

    $model->transitionTo('pending', $user);

    $auditLogs = $model->workflowAuditLogs;

    expect($auditLogs)->toHaveCount(1)
        ->and($auditLogs->first()->from_status)->toBe('draft')
        ->and($auditLogs->first()->to_status)->toBe('pending')
        ->and($auditLogs->first()->user_id)->toBe($user->id);
});

test('model can rollback to previous state', function () {
    config(['workflow-state-machine.enable_rollback' => true]);

    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    $model = TestModel::create(['title' => 'Test Task', 'status' => 'pending']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    $user = TestUser::create(['name' => 'Test User', 'email' => 'test@example.com']);

    $result = $model->rollbackToState('draft', $user);

    expect($result)->toBeTrue()
        ->and($model->fresh()->status)->toBe('draft');
});

test('model can get workflow progress', function () {
    $workflow = Workflow::create([
        'name' => 'test_workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    WorkflowProcess::create(['workflow_id' => $workflow->id, 'from_status' => 'draft', 'to_status' => 'pending', 'order' => 1]);
    WorkflowProcess::create(['workflow_id' => $workflow->id, 'from_status' => 'pending', 'to_status' => 'approved', 'order' => 2]);
    WorkflowProcess::create(['workflow_id' => $workflow->id, 'from_status' => 'approved', 'to_status' => 'completed', 'order' => 3]);

    $model = TestModel::create(['title' => 'Test Task', 'status' => 'pending']);
    $workflow->workflowable()->associate($model);
    $workflow->save();

    $progress = $model->getWorkflowProgress();

    expect($progress['current_step'])->toBe(2)
        ->and($progress['total_steps'])->toBe(4)
        ->and($progress['progress_percentage'])->toBe(50)
        ->and($progress['current_status'])->toBe('pending');
});
