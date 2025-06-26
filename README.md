# Laravel Workflow State Machine

A powerful and flexible Laravel workflow state machine library that makes it easy to manage model state transitions, rule validation, and audit tracking.

[![Tests](https://github.com/solutionforest/workflow-state-machine/workflows/Tests/badge.svg)](https://github.com/solutionforest/workflow-state-machine/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Larastan](https://img.shields.io/badge/Larastan-enabled-brightgreen.svg?style=flat)](https://github.com/larastan/larastan)
[![PHP Version](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4-787CB5.svg?style=flat)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0%20%7C%20%5E12.0-FF2D20.svg?style=flat)](https://laravel.com)

## Features

### 🎯 Core Features
- **Configurable Status Management**: Customize status lists in config files
- **Polymorphic Relations Support**: Add workflow support to any model via Traits
- **Permission Control**: Control which users can change specific model statuses
- **Rule Engine**: Define and assign multiple rules to processes
- **Automated Workflows**: Set whether status changes automatically after rules pass
- **Event-Driven Auto Transition**: Uses Laravel's event system to trigger automatic transitions
- **Workflow Roadmaps**: Group multiple processes into workflows with status roadmaps
- **Audit Logging**: Complete tracking of all status change records
- **Rollback Support**: Support for status rollback operations

### 🔧 Technical Features
- **High Test Coverage**: Using Pest testing framework
- **Code Quality**: Formatted with Pint and Larastan (Laravel-optimized PHPStan) Level 5 static analysis
- **Modular Design**: Flexible Trait system
- **Database Optimized**: Efficient polymorphic relationship design
- **Laravel Events Integration**: Automatic transition triggers on model updates

## Quick Start

### Installation

```bash
composer require solution-forest/workflow-state-machine
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=workflow-state-machine-config
```

This will publish the configuration file to `config/workflow-state-machine.php` where you can customize statuses and other settings.

### Run Migrations

```bash
php artisan migrate
```

## Basic Usage

### 1. Setup Manageable Models

Add the `HasWorkflowStates` trait to your models:

```php
use WorkflowStateMachine\Traits\HasWorkflowStates;

class Task extends Model
{
    use HasWorkflowStates;
    
    // Your model content...
    
    // The workflow relationship is automatically handled via 'workflowable' polymorphic relation
    // You can assign a workflow to this model instance:
    // $task->workflow()->associate($workflow);
}
```

### 2. Setup Permission Control

Add the `CanManageWorkflowStates` trait to user models that can change statuses:

```php
use WorkflowStateMachine\Traits\CanManageWorkflowStates;

class User extends Authenticatable
{
    use CanManageWorkflowStates;
    
    // Optional: Override permission logic
    public function canChangeWorkflowState($model, string $toStatus): bool
    {
        // Custom permission logic
        return $this->hasRole('admin') || $this->id === $model->user_id;
    }
}
```

### 3. Configure Status Lists

Customize statuses in `config/workflow-state-machine.php`:

```php
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
    
    // Other configurations...
];
```

### Custom Status Column Name

By default, the library expects your models to have a `status` column. You can customize this in two ways:

#### 1. Model-level customization (recommended)

Define a `$status_column` property in your model:

```php
use WorkflowStateMachine\Traits\HasWorkflowStates;

class Order extends Model
{
    use HasWorkflowStates;
    
    protected $fillable = ['customer_name', 'order_state'];
    
    // Customize the status column name for this model
    protected $status_column = 'order_state';
}
```

#### 2. Global configuration

Set the default status column name in `config/workflow-state-machine.php`:

```php
return [
    // Default status column name for all models
    'status_column' => 'state', // default: 'status'
    
    // Other configurations...
];
```

**Note:** Model-level `$status_column` property takes precedence over the global config setting.

#### Status Attribute Accessor

The `HasWorkflowStates` trait provides a dynamic `status` attribute accessor that automatically uses your configured status column. This means you can always access the status via `$model->status`, regardless of the actual database column name:

```php
class Order extends Model
{
    use HasWorkflowStates;
    
    protected $status_column = 'order_state'; // Custom column name
}

// You can always access status via the 'status' attribute
$order = Order::find(1);
echo $order->status; // Returns value from 'order_state' column

// Setting status also works dynamically
$order->status = 'approved'; // Sets the 'order_state' column
$order->save();

// Or use the explicit methods
echo $order->getCurrentStatus(); // Same as $order->status
$order->setStatus('completed');  // Same as $order->status = 'completed'
```

This accessor provides a consistent interface while allowing flexible database schema design.

### Custom Status Array

You can also define custom status arrays at the model level, allowing different models to have different workflow statuses:

#### Model-level status array (recommended for model-specific workflows)

Define a `$status_array` property in your model:

```php
use WorkflowStateMachine\Traits\HasWorkflowStates;

class Order extends Model
{
    use HasWorkflowStates;
    
    protected $fillable = ['customer_name', 'order_status'];
    protected $status_column = 'order_status';
    
    // Custom status array for this model
    protected $status_array = [
        'pending' => 'Pending Payment',
        'paid' => 'Payment Received', 
        'processing' => 'Processing Order',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
}
```

#### Benefits of model-level status arrays:

1. **Different workflows for different models**: Orders can have different statuses than Tasks
2. **Auto-workflow creation**: Uses model-specific statuses for starting/ending status
3. **Auto-process generation**: Creates processes based on model's status array
4. **Flexible configuration**: Each model can define its own workflow logic

#### Fallback behavior:

- **Model status array** → **Global config status array** → **Hardcoded default**

```php
// This model will use the global config status array
class Task extends Model 
{
    use HasWorkflowStates;
    
    protected $fillable = ['title', 'status'];
    // No custom $status_array, uses config
}
```

### 4. Create Workflows and Processes

```php
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Models\WorkflowRule;

// Create a workflow
$workflow = Workflow::create([
    'name' => 'task_approval_workflow',
    'description' => 'Task approval workflow',
    'starting_status' => 'draft',
    'ending_status' => 'completed',
]);

// If 'auto_create_processes' is enabled in config, processes will be automatically 
// created based on the status array (excluding 'rejected' and 'cancelled')
// Otherwise, manually create workflow processes:

$process1 = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'submit_for_review',
    'from_status' => 'draft',
    'to_status' => 'pending',
    'order' => 1,
    'auto_transition' => true,
    'completed' => false,
]);

$process2 = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'approve_task',
    'from_status' => 'pending',
    'to_status' => 'approved',
    'order' => 2,
    'auto_transition' => false,
    'completed' => false,
]);

// Assign workflow to a model instance via polymorphic relationship
$task = Task::create(['title' => 'New Task']);
$task->workflow()->associate($workflow);
$task->save();
```

### WorkflowProcess Properties

Each `WorkflowProcess` has the following properties:

- `workflow_id`: The ID of the workflow this process belongs to
- `name`: A descriptive name for the process (optional)
- `from_status`: The status this process transitions from
- `to_status`: The status this process transitions to
- `order`: The order of this process in the workflow sequence
- `auto_transition`: Whether this process should automatically transition when triggered
- `completed`: Whether this process has been completed (useful for tracking progress)
- `description`: A detailed description of what this process does (optional)

### Auto-Create Processes Feature

When `auto_create_processes` is enabled in the configuration, the library will automatically create workflow processes based on the status array when a workflow is created:

```php
// Enable auto-creation in config/workflow-state-machine.php
'auto_create_processes' => true,

// With this enabled, creating a workflow will automatically generate processes:
$workflow = Workflow::create([
    'name' => 'task_approval_workflow',
    'description' => 'Task approval workflow',
    'starting_status' => 'draft',
    'ending_status' => 'completed',
]);

// Automatically creates processes for status transitions:
// draft → pending → in_progress → review → approved → completed
// (excluding 'rejected' and 'cancelled' from the flow)

// The auto-created processes will have:
// - Sequential ordering (1, 2, 3, ...)
// - Generated names based on status transitions
// - auto_transition set to false by default
// - completed set to false by default
```

## Auto-Create Workflow Feature

This feature allows workflows to be automatically created when models with the `HasWorkflowStates` trait are created.

### Configuration

Enable auto-creation in your config file:

```php
// config/workflow-state-machine.php
return [
    // ... other config options ...
    
    // Auto-create workflow for models when they are created
    'auto_create_workflow' => true, // default: false
    'auto_workflow_name' => 'Default Workflow', // default workflow name
    
    // Auto-create processes based on status array when workflow is created
    'auto_create_processes' => true, // default: false
];
```

### Usage

Once enabled, any model that uses the `HasWorkflowStates` trait will automatically get a workflow created when the model is saved for the first time:

```php
use WorkflowStateMachine\Traits\HasWorkflowStates;

class Task extends Model
{
    use HasWorkflowStates;
    
    protected $fillable = ['title', 'description', 'status'];
}

// Enable auto-creation in config
config(['workflow-state-machine.auto_create_workflow' => true]);

// Create a new task - workflow will be auto-created
$task = Task::create([
    'title' => 'Complete project',
    'description' => 'Finish the project by Friday'
]);

// The task now has a workflow automatically assigned
echo $task->workflow->name; // "Default Workflow"
echo $task->status; // "draft" (first status from config)
```

### Auto-Creation Features

1. **Automatic Workflow Creation**: When a model is created, a workflow is automatically generated if:
   - The model uses the `HasWorkflowStates` trait
   - `auto_create_workflow` is enabled in config
   - The model doesn't already have a workflow

2. **Process Auto-Generation**: If `auto_create_processes` is enabled, the workflow will automatically create processes based on the status configuration via the `WorkflowCreated` event:

```php
// config/workflow-state-machine.php
'status' => [
    'draft' => 'Draft',
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'review' => 'Under Review',
    'approved' => 'Approved',
    'completed' => 'Completed',
],
```

3. **Initial Status Assignment**: If the model doesn't have a status when created, it will be automatically set to the first status in the configuration (typically 'draft').

4. **No Duplicate Creation**: The system checks if a model already has a workflow and won't create duplicates.

### Observer Events

The auto-creation is handled by the `WorkflowModelObserver` which listens for Eloquent `created` events. This ensures workflows are created immediately when models are saved to the database.

### Manual Control

You can also manually create workflows using the service:

```php
use WorkflowStateMachine\Services\AutoWorkflowService;

$workflow = AutoWorkflowService::createWorkflowForModel($model);
```

### Best Practices

1. **Enable selectively**: Only enable auto-creation for models that truly need workflows
2. **Configure statuses**: Ensure your status configuration matches your business needs
3. **Testing**: Always test auto-creation in your test environment first

### Disabling Auto-Creation

To disable auto-creation for specific models while keeping it enabled globally, you can override the observer behavior or check model-specific conditions in your implementation.

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `auto_create_workflow` | boolean | `false` | Enable/disable automatic workflow creation |
| `auto_workflow_name` | string | `'Default Workflow'` | Default name for auto-created workflows |
| `auto_create_processes` | boolean | `false` | Auto-create processes from status config |
| `status_column` | string | `'status'` | Default status column name for models |

### 5. Define Rules and Sample Rule Class

#### Creating Rules with Artisan Command

The quickest way to create workflow rules is using the `workflow:make-rule` command:

```bash
# Create a simple rule class
php artisan workflow:make-rule ApprovalRule

# Create a rule with custom description
php artisan workflow:make-rule DocumentValidation --description="Validates document completeness before approval"

# Create an inactive rule
php artisan workflow:make-rule PendingValidation --active=false

# Create a rule without database insertion
php artisan workflow:make-rule TestRule --no-database
```

**Command Options:**
- `name` (required): The name of the rule class
- `--description`: Description of the rule (optional)
- `--active`: Whether the rule is active by default (default: true)
- `--no-database`: Skip inserting the rule into the database

The command generates a rule class in `app/Rules/` that implements the `WorkflowRuleContract`:

```php
<?php

namespace App\Rules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class CustomRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // Your business logic here
        return true;
    }

    public function getMessage(): string
    {
        return 'Transition is not allowed by CustomRule.';
    }
}
```

#### Manual Rule Creation

Create custom rule classes that implement the rule interface:

```php
// app/WorkflowRules/UserPermissionRule.php
namespace App\WorkflowRules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class UserPermissionRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // Check if user has permission to transition this model
        if (!$user) {
            return false;
        }
        
        // Custom business logic
        if ($process->to_status === 'approved') {
            return $user->hasRole('manager') || $user->hasRole('admin');
        }
        
        if ($process->to_status === 'completed') {
            return $model->assignee_id === $user->id || $user->hasRole('admin');
        }
        
        return true;
    }
    
    public function getMessage(): string
    {
        return 'User does not have permission to perform this transition.';
    }
}
```

#### Example Rule Implementations

**User Permission Rule:**
```php
public function handle($model, WorkflowProcess $process, $user): bool
{
    if (!$user) {
        return false;
    }

    // Only managers can approve
    if ($process->to_status === 'approved') {
        return $user->hasRole('manager');
    }

    return true;
}
```

**Model State Rule:**
```php
public function handle($model, WorkflowProcess $process, $user): bool
{
    // Check if model is ready for transition
    if ($process->to_status === 'published' && !$model->is_complete) {
        return false;
    }

    return true;
}
```

**Time-based Rule:**
```php
public function handle($model, WorkflowProcess $process, $user): bool
{
    // Only allow transitions during business hours
    if ($process->to_status === 'live') {
        $hour = now()->hour;
        return $hour >= 9 && $hour <= 17;
    }

    return true;
}
```

#### Attaching Rules to Processes

```php
#### Attaching Rules to Processes

Create and assign rules to processes:

```php
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Models\WorkflowRule;

// Create rule (or use the one created by the artisan command)
$rule = WorkflowRule::create([
    'name' => 'check_user_permission',
    'description' => 'Check if user has permission to change status',
    'rule_class' => 'App\\Rules\\UserPermissionRule',
]);

// Or find an existing rule created by the command
$rule = WorkflowRule::where('rule_class', 'App\\Rules\\CustomRule')->first();

// Assign rule to process
$process = WorkflowProcess::find(1);
$process->rules()->attach($rule);
```

## Advanced Features

### Event-Driven Auto Transitions

The library automatically triggers rule checking when models or their relations are updated:

```php
// When a task is updated, auto-transition rules are checked
$task = Task::find(1);
$task->update(['assignee_id' => 5]);
// Event triggered: WorkflowAutoTransitionEvent

// When related models are updated
$task->comments()->create(['content' => 'Review completed']);
// If configured, this can also trigger auto-transition checks
```

### Workflow Roadmap

Get the complete workflow roadmap:

```php
$task = Task::find(1);
$workflow = $task->workflow;

// Get status roadmap
$roadmap = $workflow->getStatusRoadmap();
// Returns: ['draft', 'pending', 'approved', 'completed']

// Get current position in workflow
$currentStep = $task->getCurrentWorkflowStep();

// Get previous status in the workflow
$previousStatus = $task->getPreviousStatus();
// Returns: 'draft' if current status is 'pending', null if at the beginning

// Get next status in the workflow
$nextStatus = $task->getNextStatus();
// Returns: 'approved' if current status is 'pending', null if at the end

// Check if task can proceed to next step
$canProceed = $task->canProceedToNextStep($user);
```

### State Transitions

```php
$task = Task::find(1);
$user = auth()->user();

// Check if transition is possible
if ($task->canTransitionTo('completed', $user)) {
    $task->transitionTo('completed', $user);
}

// Auto transition (if rules allow)
$task->checkAutoTransition($user);

// Get available transitions
$availableTransitions = $task->getAvailableTransitions($user);
```

### Audit Log Queries

```php
// Get all status change records for a model
$auditLogs = $task->workflowAuditLogs;

// Get recent records
$recentLogs = $task->workflowAuditLogs()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Get workflow progress
$progress = $task->getWorkflowProgress();
```

### State Rollback

```php
// Rollback to previous state
$task->rollbackToPreviousState($user);

// Rollback to specific state
$task->rollbackToState('pending', $user);

// Get rollback history
$rollbackHistory = $task->getRollbackHistory();
```

## Configuration Options

### Complete Configuration Example

```php
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
    
    'enable_audit_log' => true,
    'enable_rollback' => true,
    'enable_auto_transition' => true,
    
    'auto_transition_delay' => 0, // seconds
    
    // Auto-create processes based on status array when workflow is created
    'auto_create_processes' => false, // default: false
    
    'events' => [
        'auto_check_on_model_update' => true,
        'auto_check_on_relation_update' => false,
    ],
];
```

### Sample Rule Classes

Here are some example rule classes you can create:

```php
// app/WorkflowRules/MinimumTimeRule.php
namespace App\WorkflowRules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class MinimumTimeRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // Ensure model has been in current status for at least 24 hours
        if (!$model->status_changed_at) {
            return false;
        }
        
        return $model->status_changed_at->diffInHours(now()) >= 24;
    }
    
    public function getMessage(): string
    {
        return 'Model must remain in current status for at least 24 hours.';
    }
}
```

```php
// app/WorkflowRules/RequiredFieldsRule.php
namespace App\WorkflowRules;

use WorkflowStateMachine\Contracts\WorkflowRuleContract;
use WorkflowStateMachine\Models\WorkflowProcess;

class RequiredFieldsRule implements WorkflowRuleContract
{
    public function handle($model, WorkflowProcess $process, $user): bool
    {
        // Check if required fields are filled based on target status
        if ($process->to_status === 'completed') {
            return !empty($model->completion_notes) && !empty($model->assignee_id);
        }
        
        if ($process->to_status === 'approved') {
            return !empty($model->reviewer_id) && !empty($model->review_notes);
        }
        
        return true;
    }
    
    public function getMessage(): string
    {
        return 'Required fields must be completed before status transition.';
    }
}
```

## Event System Integration

The library integrates with Laravel's event system to automatically trigger rule checking:

```php
// Events are automatically dispatched when:
// 1. Model is updated
// 2. Related models are updated (if configured)

// You can also manually trigger auto-transition checks
use WorkflowStateMachine\Events\WorkflowAutoTransitionEvent;

// Dispatch the event manually
event(new WorkflowAutoTransitionEvent($task, $user));

// Listen to workflow events in your EventServiceProvider
protected $listen = [
    'WorkflowStateMachine\Events\StatusChanged' => [
        'App\Listeners\NotifyStatusChange',
    ],
    'WorkflowStateMachine\Events\WorkflowCompleted' => [
        'App\Listeners\HandleWorkflowCompletion',
    ],
];
```

## Testing

```bash
# Run tests
composer test

# Run code formatting
composer pint

# Run static analysis with Larastan
composer larastan

# Or run PHPStan directly
composer phpstan

# Run PHPStan manually with memory limit
vendor/bin/phpstan analyse --memory-limit=256M
```

## Requirements

- PHP ^8.2
- Laravel ^11.0

## Versioning

This project follows [Semantic Versioning](https://semver.org/). Versions are tagged and released through GitHub.

For version history, see [CHANGELOG.md](CHANGELOG.md).

## Contributing

Pull requests and issues are welcome! See [VERSION_RELEASE.md](VERSION_RELEASE.md) for release procedures.

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Support

For questions or suggestions:

1. Check the [documentation](docs/)
2. Search [existing issues](https://github.com/solution-forest/workflow-state-machine/issues)
3. Create a new [Issue](https://github.com/solution-forest/workflow-state-machine/issues/new)

---

**Make workflow management simple and powerful!** 🚀
