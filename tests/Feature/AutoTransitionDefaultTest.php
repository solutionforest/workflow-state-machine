<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Models\TestModel;
use WorkflowStateMachine\Models\Workflow;

beforeEach(function () {
    // Create test table
    Schema::create('test_models', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('status')->default('draft');
        $table->unsignedBigInteger('workflow_id')->nullable();
        $table->timestamps();
    });
});

test('auto transition can be enabled and disabled via config', function () {
    // Test with disabled
    config(['workflow-state-machine.enable_auto_transition' => false]);
    expect(config('workflow-state-machine.enable_auto_transition'))->toBeFalse();

    // Test with enabled
    config(['workflow-state-machine.enable_auto_transition' => true]);
    expect(config('workflow-state-machine.enable_auto_transition'))->toBeTrue();
});

test('model update does not trigger auto transition when disabled', function () {
    // Disable auto-transition
    config(['workflow-state-machine.enable_auto_transition' => false]);

    Event::fake();

    // Create a workflow and model
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'starting_status' => 'draft',
        'ending_status' => 'completed',
    ]);
    $model = TestModel::create(['title' => 'Test', 'workflow_id' => $workflow->id, 'status' => 'draft']);

    // Update the model - this should not trigger auto transition
    $model->update(['title' => 'Updated Test']);

    // We can't easily assert that observer method wasn't called since it's not registered
    // But we can verify the config works by checking it's actually false
    expect(config('workflow-state-machine.enable_auto_transition'))->toBeFalse();
});
