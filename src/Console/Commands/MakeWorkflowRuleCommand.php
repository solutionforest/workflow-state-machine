<?php

namespace WorkflowStateMachine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WorkflowStateMachine\Models\WorkflowRule;

class MakeWorkflowRuleCommand extends Command
{
    protected $signature = 'workflow:make-rule {name : The name of the rule class}
                            {--description= : Description of the rule}
                            {--active=true : Whether the rule is active by default}
                            {--no-database : Do not insert into database}';

    protected $description = 'Create a new workflow rule class and optionally insert into database';

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = Str::studly($name);

        // Ensure the class name ends with 'Rule'
        if (! Str::endsWith($className, 'Rule')) {
            $className .= 'Rule';
        }

        $description = $this->option('description') ?: "Custom rule: {$className}";
        $isActive = $this->option('active') === 'true' || $this->option('active') === true;

        // Create the rule class file
        $ruleCreated = $this->createRuleClass($className);
        if ($ruleCreated === false) {
            $this->error("Failed to create rule class {$className}");

            return self::FAILURE;
        } elseif ($ruleCreated === null) {
            $this->info('Rule class creation skipped.');

            return self::SUCCESS;
        } else {
            $this->info("Rule class {$className} created successfully!");
        }

        // Insert into database if not disabled
        if (! $this->option('no-database')) {
            if ($this->insertRuleIntoDatabase($className, $description, $isActive)) {
                $this->info("Rule {$className} inserted into database successfully!");
            } else {
                $this->error('Failed to insert rule into database');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line("1. Implement your business logic in the handle() method of {$className}");
        $this->line('2. Customize the error message in the getMessage() method');
        $this->line('3. Attach the rule to workflow processes as needed');

        return self::SUCCESS;
    }

    protected function createRuleClass(string $className): ?bool
    {
        $path = app_path("Rules/{$className}.php");

        // Check if file already exists
        if (File::exists($path)) {
            if (! $this->confirm("Rule class {$className} already exists. Overwrite?", false)) {
                return null; // User chose not to overwrite
            }
        }

        // Ensure directory exists
        $directory = dirname($path);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = $this->getRuleStub();
        $content = str_replace(
            ['{{className}}', '{{namespace}}'],
            [$className, 'App\\Rules'],
            $stub
        );

        return File::put($path, $content) !== false;
    }

    protected function insertRuleIntoDatabase(string $className, string $description, bool $isActive): bool
    {
        try {
            $ruleClass = "App\\Rules\\{$className}";

            // Check if rule already exists
            $existingRule = WorkflowRule::where('rule_class', $ruleClass)->first();

            if ($existingRule) {
                if (! $this->confirm("Rule {$className} already exists in database. Update?", false)) {
                    return false;
                }

                $existingRule->update([
                    'name' => $className,
                    'description' => $description,
                    'is_active' => $isActive,
                ]);

                return true;
            }

            WorkflowRule::create([
                'name' => $className,
                'description' => $description,
                'rule_class' => $ruleClass,
                'is_active' => $isActive,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->error("Database error: {$e->getMessage()}");

            return false;
        }
    }

    protected function getRuleStub(): string
    {
        return <<<'EOF'
<?php

namespace {{namespace}};

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class {{className}} implements WorkflowRuleContract
{
    /**
     * Handle the rule check
     *
     * @param mixed $model The model being transitioned
     * @param WorkflowProcess $process The workflow process
     * @param mixed $user The user performing the transition
     * @return bool True if the rule passes, false otherwise
     */
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // TODO: Implement your business logic here
        
        // Example: Check user permissions
        // if (!$user) {
        //     return false;
        // }
        
        // Example: Check model state
        // if ($process->to_status === 'approved' && !$model->is_ready_for_approval) {
        //     return false;
        // }
        
        // Example: Check user roles
        // if ($process->to_status === 'published' && !$user->hasRole('publisher')) {
        //     return false;
        // }
        
        return true; // Default: allow transition
    }

    /**
     * Get the error message when rule fails
     *
     * @return string
     */
    public function getMessage(): string
    {
        return 'Transition is not allowed by {{className}}.';
    }
}
EOF;
    }
}
