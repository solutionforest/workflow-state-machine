<?php

namespace WorkflowStateMachine\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class CustomStatusColumnModel extends Model
{
    use HasWorkflowStates;

    protected $table = 'custom_status_models';

    protected $fillable = ['name', 'custom_status'];

    protected $status_column = 'custom_status';
}
