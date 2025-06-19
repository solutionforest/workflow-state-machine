<?php

namespace WorkflowStateMachine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use WorkflowStateMachine\Models\Workflow;

class WorkflowCreated
{
    use Dispatchable;

    public function __construct(
        public Workflow $workflow
    ) {}
}
