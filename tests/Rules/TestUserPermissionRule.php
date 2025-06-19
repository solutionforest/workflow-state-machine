<?php

namespace Tests\Rules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class TestUserPermissionRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($process->to_status === 'approved') {
            return $user->role === 'admin' || $user->role === 'manager';
        }

        return true;
    }

    public function getMessage(): string
    {
        return 'User does not have permission to perform this transition.';
    }
}
