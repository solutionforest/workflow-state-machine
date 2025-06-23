<?php

namespace WorkflowStateMachine\Services;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class AutoWorkflowService
{
    /**
     * Auto-create workflow for a model if enabled in config
     */
    public static function createWorkflowForModel(Model $model): ?Workflow
    {
        if (! static::shouldAutoCreateWorkflow($model)) {
            return null;
        }

        // Check if model already has a workflow
        $existingWorkflow = null;
        if (method_exists($model, 'workflows')) {
            $existingWorkflow = $model->workflows()->first();
        }

        if ($existingWorkflow) {
            return $existingWorkflow;
        }

        $workflowName = config('workflow-state-machine.auto_workflow_name', 'Default Workflow');

        // Get default statuses from model or config
        $statuses = method_exists($model, 'getStatusArray') ? $model->getStatusArray() : config('workflow-state-machine.status', ['draft' => 'Draft']);
        $firstStatus = array_key_first($statuses);
        $lastStatus = array_key_last($statuses);

        // Create new workflow
        $workflow = Workflow::create([
            'name' => $workflowName,
            'description' => 'Auto-generated workflow for '.class_basename($model),
            'workflowable_type' => get_class($model),
            'workflowable_id' => $model->getKey(),
            'starting_status' => $firstStatus,
            'ending_status' => $lastStatus,
        ]);

        // Set initial status if model doesn't have one
        $statusColumn = method_exists($model, 'getStatusColumnName') ? $model->getStatusColumnName() : 'status';
        if (! $model->getAttribute($statusColumn)) {
            $model->update([$statusColumn => $firstStatus]);
        }

        return $workflow;
    }

    /**
     * Check if auto-creation is enabled and model uses workflow trait
     */
    protected static function shouldAutoCreateWorkflow(Model $model): bool
    {
        // Check if auto-creation is enabled
        if (! config('workflow-state-machine.auto_create_workflow', false)) {
            return false;
        }

        // Check if model uses the HasWorkflowStates trait
        $traits = class_uses_recursive(get_class($model));

        return in_array(HasWorkflowStates::class, $traits);
    }
}
