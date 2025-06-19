<?php

namespace WorkflowStateMachine\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use WorkflowStateMachine\WorkflowStateMachineServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            WorkflowStateMachineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('workflow-state-machine', [
            'status' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'completed' => 'Completed',
                'rejected' => 'Rejected',
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
            'enable_audit_log' => true,
            'enable_rollback' => true,
            'enable_auto_transition' => true,
            'auto_transition_delay' => 0,
            'auto_create_processes' => false,
            'events' => [
                'auto_check_on_model_update' => true,
                'auto_check_on_relation_update' => false,
            ],
        ]);
    }
}
