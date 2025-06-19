<?php

namespace WorkflowStateMachine\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WorkflowAutoTransitionEvent
{
    use Dispatchable;

    public function __construct(
        public $model,
        public $user = null
    ) {}
}
