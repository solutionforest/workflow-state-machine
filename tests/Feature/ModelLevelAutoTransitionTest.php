<?php

use Illuminate\Support\Facades\Schema;
use Tests\Models\TestModel;
use Tests\Models\TestUser;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;

// Test model with auto-transition enabled
class TestModelWithAutoTransition extends TestModel
{
    protected $enable_auto_transition = true;

    protected $table = 'test_models';
}

// Test model with auto-transition disabled
class TestModelWithoutAutoTransition extends TestModel
{
    protected $enable_auto_transition = false;

    protected $table = 'test_models';
}

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

test('model can enable auto-transition at model level', function () {
    // Set global config to disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);

    $model = new TestModelWithAutoTransition([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);
    $model->save();

    // Model should have auto-transition enabled despite global config being disabled
    expect($model->isAutoTransitionEnabled())->toBeTrue();
});

test('model can disable auto-transition at model level', function () {
    // Set global config to enabled
    config(['workflow-state-machine.enable_auto_transition' => true]);

    $model = new TestModelWithoutAutoTransition([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);
    $model->save();

    // Model should have auto-transition disabled despite global config being enabled
    expect($model->isAutoTransitionEnabled())->toBeFalse();
});

test('model falls back to global config when no model-level setting', function () {
    // Test with global config enabled
    config(['workflow-state-machine.enable_auto_transition' => true]);

    $model = TestModel::create([
        'title' => 'Test Task',
        'status' => 'draft',
    ]);

    expect($model->isAutoTransitionEnabled())->toBeTrue();

    // Test with global config disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);

    $model2 = TestModel::create([
        'title' => 'Test Task 2',
        'status' => 'draft',
    ]);

    expect($model2->isAutoTransitionEnabled())->toBeFalse();
});

test('checkAutoTransition respects model-level auto-transition setting', function () {
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin',
    ]);

    // Create workflow and process
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for auto-transition',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => true, // Process allows auto-transition
        'completed' => false,
    ]);

    // Test model with auto-transition enabled
    $modelEnabled = new TestModelWithAutoTransition([
        'title' => 'Test Task Enabled',
        'status' => 'draft',
    ]);
    $modelEnabled->save();
    $modelEnabled->workflows()->save($workflow);

    // Should auto-transition
    $result = $modelEnabled->checkAutoTransition($user);
    expect($result)->toBeTrue();
    expect($modelEnabled->fresh()->getCurrentStatus())->toBe('pending');

    // Test model with auto-transition disabled
    $modelDisabled = new TestModelWithoutAutoTransition([
        'title' => 'Test Task Disabled',
        'status' => 'draft',
    ]);
    $modelDisabled->save();
    $modelDisabled->workflows()->save($workflow);

    // Should NOT auto-transition
    $result = $modelDisabled->checkAutoTransition($user);
    expect($result)->toBeFalse();
    expect($modelDisabled->fresh()->getCurrentStatus())->toBe('draft');
});

test('model-level auto-transition setting takes precedence over global config', function () {
    // Set global config to disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);

    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin',
    ]);

    // Create workflow and process
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'description' => 'Test workflow for precedence test',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    WorkflowProcess::create([
        'workflow_id' => $workflow->id,
        'name' => 'draft_to_pending',
        'from_status' => 'draft',
        'to_status' => 'pending',
        'order' => 1,
        'auto_transition' => true,
        'completed' => false,
    ]);

    // Model with auto-transition enabled should work despite global config being disabled
    $model = new TestModelWithAutoTransition([
        'title' => 'Test Task Override',
        'status' => 'draft',
    ]);
    $model->save();
    $model->workflows()->save($workflow);

    $result = $model->checkAutoTransition($user);
    expect($result)->toBeTrue();
    expect($model->fresh()->getCurrentStatus())->toBe('pending');
});
