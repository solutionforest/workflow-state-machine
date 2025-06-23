<?php

return [
    'status' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'table_names' => [
        'workflows' => 'workflows',
        'workflow_rules' => 'workflow_rules',
        'workflow_processes' => 'workflow_processes',
        'workflow_process_rules' => 'workflow_process_rules',
        'workflow_audit_logs' => 'workflow_audit_logs',
    ],

    'morph_name' => 'workflowable',

    // Default status column name for models
    'status_column' => 'status',

    'enable_audit_log' => true,
    'enable_rollback' => true,
    'enable_auto_transition' => true,

    'auto_transition_delay' => 0, // seconds

    // Auto-create processes based on status array when workflow is created
    'auto_create_processes' => false, // default: false

    // Auto-create workflow for models when they are created
    'auto_create_workflow' => false, // default: false
    'auto_workflow_name' => 'Default Workflow', // default workflow name

    'events' => [
        'auto_check_on_model_update' => true,
        'auto_check_on_relation_update' => false,
    ],
];
