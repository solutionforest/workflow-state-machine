<?php

namespace WorkflowStateMachine;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use WorkflowStateMachine\Console\Commands\MakeWorkflowRuleCommand;
use WorkflowStateMachine\Console\Commands\WorkflowInstallCommand;
use WorkflowStateMachine\Events\WorkflowCreated;
use WorkflowStateMachine\Listeners\CreateWorkflowProcesses;
use WorkflowStateMachine\Observers\WorkflowModelObserver;

class WorkflowStateMachineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/workflow-state-machine.php',
            'workflow-state-machine'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/workflow-state-machine.php' => config_path('workflow-state-machine.php'),
            ], 'workflow-state-machine-config');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->commands([
                WorkflowInstallCommand::class,
                MakeWorkflowRuleCommand::class,
            ]);
        }

        // Register event listeners
        Event::listen(WorkflowCreated::class, CreateWorkflowProcesses::class);

        // Register model observer for auto-creation and auto-transition events
        if (config('workflow-state-machine.auto_create_workflow', false)) {
            Event::listen('eloquent.created: *', [WorkflowModelObserver::class, 'created']);
        }

        // Register updated listener only if auto-transition is enabled
        if (config('workflow-state-machine.enable_auto_transition', false)) {
            Event::listen('eloquent.updated: *', [WorkflowModelObserver::class, 'updated']);
        }
    }
}
