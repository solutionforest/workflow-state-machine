<?php

namespace WorkflowStateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowAuditLog extends Model
{
    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'from_status',
        'to_status',
        'user_id',
        'user_type',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('config') && config('workflow-state-machine.table_names.workflow_audit_logs')) {
            $this->setTable(config('workflow-state-machine.table_names.workflow_audit_logs'));
        } else {
            $this->setTable('workflow_audit_logs');
        }
    }

    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }
}
