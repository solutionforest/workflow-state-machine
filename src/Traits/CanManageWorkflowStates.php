<?php

namespace WorkflowStateMachine\Traits;

trait CanManageWorkflowStates
{
    /**
     * Check if user can change workflow state
     * Override this method in your User model for custom logic
     */
    public function canChangeWorkflowState($model, string $toStatus): bool
    {
        // Default implementation - can be overridden
        return true;
    }
}
