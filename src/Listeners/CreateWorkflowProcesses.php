<?php

namespace WorkflowStateMachine\Listeners;

use WorkflowStateMachine\Events\WorkflowCreated;
use WorkflowStateMachine\Models\WorkflowProcess;

class CreateWorkflowProcesses
{
    public function handle(WorkflowCreated $event): void
    {
        $workflow = $event->workflow;

        // Check if auto-create processes is enabled
        if (! \config('workflow-state-machine.auto_create_processes', false)) {
            return;
        }

        // Skip if workflow already has processes
        if ($workflow->processes()->exists()) {
            return;
        }

        // Get status array and auto-transition setting from the workflowable model if available, otherwise use config
        $statuses = [];
        $autoTransition = null; // Use null to indicate no model-level setting found
        if ($workflow->workflowable_type && $workflow->workflowable_id) {
            $modelClass = $workflow->workflowable_type;
            if (class_exists($modelClass)) {
                $model = $modelClass::find($workflow->workflowable_id);
                if ($model) {
                    if (method_exists($model, 'getStatusArray')) {
                        $statuses = $model->getStatusArray();
                    }
                    // Get auto-transition setting from model
                    if (method_exists($model, 'isAutoTransitionEnabled')) {
                        $autoTransition = $model->isAutoTransitionEnabled();
                    }
                }
            }
        }

        // Fallback to config if no model-specific status array or auto-transition setting found
        if (empty($statuses)) {
            $statuses = \config('workflow-state-machine.status', []);
        }
        
        // If model doesn't have auto-transition setting, fallback to config
        if ($autoTransition === null) {
            $autoTransition = \config('workflow-state-machine.enable_auto_transition', false);
        }

        $excludedStatuses = ['rejected', 'cancelled'];

        // Filter out excluded statuses
        $filteredStatuses = collect($statuses)
            ->keys()
            ->reject(fn ($status) => in_array($status, $excludedStatuses))
            ->values()
            ->toArray();

        if (count($filteredStatuses) < 2) {
            return;
        }

        $order = 1;

        // Create processes for sequential status transitions
        for ($i = 0; $i < count($filteredStatuses) - 1; $i++) {
            $fromStatus = $filteredStatuses[$i];
            $toStatus = $filteredStatuses[$i + 1];

            WorkflowProcess::create([
                'workflow_id' => $workflow->id,
                'name' => "transition_from_{$fromStatus}_to_{$toStatus}",
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'order' => $order++,
                'auto_transition' => $autoTransition,
                'completed' => false,
                'description' => "Auto-generated process for {$fromStatus} to {$toStatus} transition",
            ]);
        }
    }
}
