<?php

namespace WorkflowStateMachine\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WorkflowCompleted
{
    use Dispatchable;

    public function __construct(
        public $model,
        public $user = null
    ) {}
}
