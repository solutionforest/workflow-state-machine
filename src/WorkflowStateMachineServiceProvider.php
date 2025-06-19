<?php

namespace WorkflowStateMachine;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
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
            ]);
        }

        // Register event listeners
        Event::listen(WorkflowCreated::class, CreateWorkflowProcesses::class);

        // Register model observer for auto-transition events
        if (config('workflow-state-machine.events.auto_check_on_model_update')) {
            Event::listen('eloquent.updated: *', [WorkflowModelObserver::class, 'updated']);
        }
    }
}
