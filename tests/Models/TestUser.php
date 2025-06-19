<?php

namespace Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use WorkflowStateMachine\Traits\CanManageWorkflowStates;

class TestUser extends Authenticatable
{
    use CanManageWorkflowStates;

    protected $fillable = ['name', 'email', 'role'];

    protected $table = 'test_users';

    public function canChangeWorkflowState($model, string $toStatus): bool
    {
        // Allow admins to change any status
        if ($this->role === 'admin') {
            return true;
        }

        // Regular users can only approve if they are the assigned user
        if ($toStatus === 'approved') {
            return $model->assignee_id === $this->id;
        }

        return true;
    }
}
