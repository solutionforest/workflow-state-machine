<?php

namespace WorkflowStateMachine\Observers;

use WorkflowStateMachine\Services\AutoWorkflowService;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class WorkflowModelObserver
{
    public function created($eventName, array $data): void
    {
        // Extract model from event data
        $model = $data[0] ?? null;

        if (! $model) {
            return;
        }

        // Check if model uses workflow states
        if (! in_array(HasWorkflowStates::class, class_uses_recursive($model))) {
            return;
        }

        // Auto-create workflow for the model
        AutoWorkflowService::createWorkflowForModel($model);
    }

    public function updated($eventName, array $data): void
    {
        // Extract model from event data
        $model = $data[0] ?? null;

        if (! $model) {
            return;
        }

        // Check if model uses workflow states
        if (! in_array(HasWorkflowStates::class, class_uses_recursive($model))) {
            return;
        }

        // Skip if no workflow assigned
        if (! $model->workflow) {
            return;
        }

        // Check for auto-transition after model update
        if (\config('workflow-state-machine.enable_auto_transition', true)) {
            $delay = \config('workflow-state-machine.auto_transition_delay', 0);

            if ($delay > 0) {
                // Dispatch delayed event
                \dispatch(function () use ($model) {
                    $model->checkAutoTransition();
                })->delay(now()->addSeconds($delay));
            } else {
                // Immediate check
                $model->checkAutoTransition();
            }
        }
    }
}
