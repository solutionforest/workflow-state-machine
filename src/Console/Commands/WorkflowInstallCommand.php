<?php

namespace WorkflowStateMachine\Console\Commands;

use Illuminate\Console\Command;

class WorkflowInstallCommand extends Command
{
    protected $signature = 'workflow:install {--force : Overwrite existing files}';

    protected $description = 'Install the Workflow State Machine package';

    public function handle(): int
    {
        $this->info('Installing Workflow State Machine...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'workflow-state-machine-config',
            '--force' => $this->option('force'),
        ]);

        // Run migrations
        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('Workflow State Machine installed successfully!');

        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Configure your statuses in config/workflow-state-machine.php');
        $this->line('2. Add the HasWorkflowStates trait to your models');
        $this->line('3. Add the CanManageWorkflowStates trait to your user models');
        $this->line('4. Create workflows and processes programmatically');

        return self::SUCCESS;
    }
}
