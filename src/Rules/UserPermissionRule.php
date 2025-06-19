<?php

namespace WorkflowStateMachine\Rules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class UserPermissionRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // Check if user has permission to transition this model
        if (! $user) {
            return false;
        }

        // Custom business logic
        if ($process->to_status === 'approved') {
            return $user->hasRole('manager') || $user->hasRole('admin');
        }

        if ($process->to_status === 'completed') {
            return $model->assignee_id === $user->id || $user->hasRole('admin');
        }

        return true;
    }

    public function getMessage(): string
    {
        return 'User does not have permission to perform this transition.';
    }
}
