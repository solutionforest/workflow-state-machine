# Workflow State Machine Demo

This demo shows how to use the Workflow State Machine library with a simple Task management system.

## Setup

1. Install dependencies:
```bash
composer install
```

2. Run migrations:
```bash
php artisan migrate
```

3. Publish config:
```bash
php artisan vendor:publish --tag=workflow-state-machine-config
```

## Demo Usage

```php
<?php

use App\Models\Task;
use App\Models\User;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowProcess;
use WorkflowStateMachine\Models\WorkflowRule;
use App\WorkflowRules\UserPermissionRule;

// 1. Create a workflow
$workflow = Workflow::create([
    'name' => 'task_approval_workflow',
    'description' => 'Task approval workflow',
    'starting_status' => 'draft',
    'ending_status' => 'completed',
]);

// 2. Create workflow processes
$submitProcess = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'submit_for_review',
    'from_status' => 'draft',
    'to_status' => 'pending',
    'order' => 1,
    'auto_transition' => false,
]);

$approveProcess = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'approve_task',
    'from_status' => 'pending',
    'to_status' => 'approved',
    'order' => 2,
    'auto_transition' => false,
]);

$completeProcess = WorkflowProcess::create([
    'workflow_id' => $workflow->id,
    'name' => 'complete_task',
    'from_status' => 'approved',
    'to_status' => 'completed',
    'order' => 3,
    'auto_transition' => true,
]);

// 3. Create rules
$permissionRule = WorkflowRule::create([
    'name' => 'user_permission_check',
    'description' => 'Check if user has permission to change status',
    'rule_class' => UserPermissionRule::class,
]);

// 4. Attach rules to processes
$approveProcess->rules()->attach($permissionRule);

// 5. Create a task and assign workflow
$task = Task::create([
    'title' => 'Design new landing page',
    'description' => 'Create a modern landing page for our product',
    'status' => 'draft',
    'assignee_id' => 1,
]);

$task->workflow()->associate($workflow);
$task->save();

// 6. Create users
$manager = User::create([
    'name' => 'John Manager',
    'email' => 'manager@example.com',
    'role' => 'manager',
]);

$developer = User::create([
    'name' => 'Jane Developer',
    'email' => 'developer@example.com',
    'role' => 'developer',
]);

// 7. Test workflow transitions
echo "Current status: " . $task->status . "\n";
echo "Next status: " . $task->getNextStatus() . "\n";

// Submit for review
if ($task->canTransitionTo('pending', $developer)) {
    $task->transitionTo('pending', $developer, 'Task ready for review');
    echo "Task submitted for review\n";
}

// Try to approve (should fail for developer)
if (!$task->canTransitionTo('approved', $developer)) {
    echo "Developer cannot approve task\n";
}

// Manager approves
if ($task->canTransitionTo('approved', $manager)) {
    $task->transitionTo('approved', $manager, 'Task looks good');
    echo "Task approved by manager\n";
}

// Auto-transition to completed (if auto_transition is true)
$task->checkAutoTransition($manager);
echo "Final status: " . $task->status . "\n";

// 8. Check workflow progress
$progress = $task->getWorkflowProgress();
echo "Progress: {$progress['current_step']}/{$progress['total_steps']} ({$progress['progress_percentage']}%)\n";

// 9. View audit logs
foreach ($task->workflowAuditLogs as $log) {
    echo "Changed from {$log->from_status} to {$log->to_status} by {$log->user->name}\n";
}

// 10. Get available transitions
$availableTransitions = $task->getAvailableTransitions($manager);
echo "Available transitions: " . implode(', ', $availableTransitions) . "\n";
```

## Running Tests

```bash
# Run all tests
composer test

# Run code formatting
composer pint

# Run static analysis
composer phpstan
```

## Expected Output

```
Current status: draft
Next status: pending
Task submitted for review
Developer cannot approve task
Task approved by manager
Final status: completed
Progress: 4/4 (100%)
Changed from draft to pending by Jane Developer
Changed from pending to approved by John Manager
Changed from approved to completed by John Manager
Available transitions: 
```
