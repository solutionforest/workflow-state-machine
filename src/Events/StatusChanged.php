<?php

namespace WorkflowStateMachine\Events;

use Illuminate\Foundation\Events\Dispatchable;

class StatusChanged
{
    use Dispatchable;

    public function __construct(
        public $model,
        public string $fromStatus,
        public string $toStatus,
        public $user = null
    ) {}
}
