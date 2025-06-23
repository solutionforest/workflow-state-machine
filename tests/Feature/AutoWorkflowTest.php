<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Services\AutoWorkflowService;
use WorkflowStateMachine\Tests\Models\AutoWorkflowTestModel;

beforeEach(function () {
    // Create test table
    if (! Schema::hasTable('auto_workflow_test_models')) {
        Schema::create('auto_workflow_test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up
    AutoWorkflowTestModel::truncate();
    Workflow::truncate();
});

it('does not auto-create workflow when disabled', function () {
    config(['workflow-state-machine.auto_create_workflow' => false]);

    $model = AutoWorkflowTestModel::create(['name' => 'Test Model']);

    expect($model->workflow)->toBeNull();
});

it('auto-creates workflow when enabled', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Test Workflow',
    ]);

    $model = AutoWorkflowTestModel::create(['name' => 'Test Model']);

    // Manually trigger auto-creation since we're testing the service directly
    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->not->toBeNull();
    expect($workflow->name)->toBe('Test Workflow');
    expect($workflow->workflowable_type)->toBe(AutoWorkflowTestModel::class);
    expect($workflow->workflowable_id)->toBe($model->id);
});

it('sets initial status when model has no status', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Test Workflow',
    ]);

    $model = AutoWorkflowTestModel::create(['name' => 'Test Model']); // No status set

    // Manually trigger auto-creation
    AutoWorkflowService::createWorkflowForModel($model);

    $model->refresh();
    expect($model->status)->toBe('draft'); // First status from config
});

it('does not create workflow for models without trait', function () {
    config(['workflow-state-machine.auto_create_workflow' => true]);

    $modelClass = new class extends Model
    {
        protected $table = 'auto_workflow_test_models';

        protected $fillable = ['name'];
    };

    $model = $modelClass::create(['name' => 'Test Model']);

    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow)->toBeNull();
});

it('does not create duplicate workflow for same model', function () {
    config([
        'workflow-state-machine.auto_create_workflow' => true,
        'workflow-state-machine.auto_workflow_name' => 'Test Workflow',
    ]);

    $model = AutoWorkflowTestModel::create(['name' => 'Test Model']);

    // Create workflow manually first
    $existingWorkflow = Workflow::create([
        'name' => 'Existing Workflow',
        'description' => 'Existing workflow',
        'workflowable_type' => AutoWorkflowTestModel::class,
        'workflowable_id' => $model->id,
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);

    // Try to auto-create - should return existing workflow
    $workflow = AutoWorkflowService::createWorkflowForModel($model);

    expect($workflow->id)->toBe($existingWorkflow->id);
    expect(Workflow::where('workflowable_id', $model->id)->count())->toBe(1);
});
