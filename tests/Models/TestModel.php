<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class TestModel extends Model
{
    use HasWorkflowStates;

    protected $fillable = ['title', 'status', 'assignee_id'];

    protected $table = 'test_models';
}
