<?php

namespace WorkflowStateMachine\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class DefaultStatusColumnModel extends Model
{
    use HasWorkflowStates;

    protected $table = 'default_status_models';

    protected $fillable = ['name', 'status'];
}
