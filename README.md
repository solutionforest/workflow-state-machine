# Laravel Workflow State Machine

A powerful and flexible Laravel workflow state machine library that makes it easy to manage model state transitions, rule validation, and audit tracking.

[![Tests](https://github.com/solution-forest/workflow-state-machine/workflows/tests/badge.svg)](https://github.com/solution-forest/workflow-state-machine/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Larastan](https://img.shields.io/badge/Larastan-enabled-brightgreen.svg?style=flat)](https://github.com/larastan/larastan)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-787CB5.svg?style=flat)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0-FF2D20.svg?style=flat)](https://laravel.com)

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
]);

$process2 = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'approve_task',
    'from_status' => 'pending',
    'to_status' => 'approved',
    'order' => 2,
    'auto_transition' => false,
]);

// Assign workflow to a model instance via polymorphic relationship
$task = Task::create(['title' => 'New Task']);
$task->workflow()->associate($workflow);
$task->save();
```

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
```

### 5. Define Rules and Sample Rule Class

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

Create and assign rules to processes:

```php
// Create rule
$rule = WorkflowRule::create([
    'name' => 'check_user_permission',
    'description' => 'Check if user has permission to change status',
    'rule_class' => 'App\WorkflowRules\UserPermissionRule',
]);

// Assign rule to process
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
