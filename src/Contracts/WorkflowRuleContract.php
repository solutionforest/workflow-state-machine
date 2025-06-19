<?php

namespace WorkflowStateMachine\Contracts;

use WorkflowStateMachine\Models\WorkflowProcess;

interface WorkflowRuleContract
{
    /**
     * Handle the rule check
     */
    public function handle($model, WorkflowProcess $process, $user): bool;

    /**
     * Get the error message when rule fails
     */
    public function getMessage(): string;
}
