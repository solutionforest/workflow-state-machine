<?php

namespace WorkflowStateMachine\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class AutoWorkflowTestModel extends Model
{
    use HasWorkflowStates;

    protected $table = 'auto_workflow_test_models';

    protected $fillable = ['name', 'status'];
}
