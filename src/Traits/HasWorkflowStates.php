<?php

namespace WorkflowStateMachine\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use WorkflowStateMachine\Events\StatusChanged;
use WorkflowStateMachine\Events\WorkflowCompleted;
use WorkflowStateMachine\Models\Workflow;
use WorkflowStateMachine\Models\WorkflowAuditLog;
use WorkflowStateMachine\Models\WorkflowProcess;

trait HasWorkflowStates
{
    public function workflows(): MorphMany
    {
        $morphName = function_exists('config') && config('workflow-state-machine.morph_name')
            ? config('workflow-state-machine.morph_name')
            : 'workflowable';

        return $this->morphMany(Workflow::class, $morphName);
    }

    public function getWorkflowAttribute()
    {
        return $this->workflows()->first();
    }

    public function workflowAuditLogs()
    {
        return $this->morphMany(WorkflowAuditLog::class, 'workflowable');
    }

    public function canTransitionTo(string $toStatus, $user = null): bool
    {
        if (! $this->workflow) {
            return false;
        }

        $process = $this->getProcessForTransition($this->status, $toStatus);

        if (! $process) {
            return false;
        }

        // Check if user has permission to change status
        if ($user && method_exists($user, 'canChangeWorkflowState')) {
            if (! $user->canChangeWorkflowState($this, $toStatus)) {
                return false;
            }
        }

        // Check all rules for this process
        return $process->allRulesPassed($this, $user);
    }

    public function transitionTo(string $toStatus, $user = null, ?string $notes = null): bool
    {
        if (! $this->canTransitionTo($toStatus, $user)) {
            return false;
        }

        $fromStatus = $this->status;

        // Update the status
        $this->update(['status' => $toStatus]);

        // Log the change
        $enableAuditLog = function_exists('config') ? config('workflow-state-machine.enable_audit_log', true) : true;
        if ($enableAuditLog) {
            $this->logStatusChange($fromStatus, $toStatus, $user, $notes);
        }

        // Dispatch events
        event(new StatusChanged($this, $fromStatus, $toStatus, $user));

        // Check if workflow is completed
        if ($toStatus === $this->workflow->ending_status) {
            event(new WorkflowCompleted($this, $user));
        }

        return true;
    }

    public function checkAutoTransition($user = null): bool
    {
        if (! $this->workflow) {
            return false;
        }

        $nextStatus = $this->getNextStatus();

        if (! $nextStatus) {
            return false;
        }

        $process = $this->getProcessForTransition($this->status, $nextStatus);

        if (! $process || ! $process->auto_transition) {
            return false;
        }

        return $this->transitionTo($nextStatus, $user, 'Auto-transition');
    }

    public function getNextStatus(): ?string
    {
        return $this->workflow?->getNextStatus($this->status);
    }

    public function getPreviousStatus(): ?string
    {
        return $this->workflow?->getPreviousStatus($this->status);
    }

    public function getCurrentWorkflowStep(): ?int
    {
        if (! $this->workflow) {
            return null;
        }

        $roadmap = $this->workflow->getStatusRoadmap();

        return array_search($this->status, $roadmap) + 1;
    }

    public function canProceedToNextStep($user = null): bool
    {
        $nextStatus = $this->getNextStatus();

        if (! $nextStatus) {
            return false;
        }

        return $this->canTransitionTo($nextStatus, $user);
    }

    public function getWorkflowProgress(): array
    {
        if (! $this->workflow) {
            return [];
        }

        $roadmap = $this->workflow->getStatusRoadmap();
        $currentIndex = array_search($this->status, $roadmap);

        return [
            'current_step' => $currentIndex + 1,
            'total_steps' => count($roadmap),
            'progress_percentage' => (int) round((($currentIndex + 1) / count($roadmap)) * 100),
            'roadmap' => $roadmap,
            'current_status' => $this->status,
        ];
    }

    public function rollbackToPreviousState($user = null): bool
    {
        $previousStatus = $this->getPreviousStatus();

        if (! $previousStatus) {
            return false;
        }

        return $this->rollbackToState($previousStatus, $user);
    }

    public function rollbackToState(string $toStatus, $user = null): bool
    {
        $enableRollback = function_exists('config') ? config('workflow-state-machine.enable_rollback', true) : true;
        if (! $enableRollback) {
            return false;
        }

        $fromStatus = $this->status;

        // Update the status
        $this->update(['status' => $toStatus]);

        // Log the rollback
        $enableAuditLog = function_exists('config') ? config('workflow-state-machine.enable_audit_log', true) : true;
        if ($enableAuditLog) {
            $this->logStatusChange($fromStatus, $toStatus, $user, 'Rollback');
        }

        // Dispatch events
        event(new StatusChanged($this, $fromStatus, $toStatus, $user));

        return true;
    }

    public function getRollbackHistory()
    {
        return $this->workflowAuditLogs()
            ->where('notes', 'Rollback')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAvailableTransitions($user = null): array
    {
        if (! $this->workflow) {
            return [];
        }

        $availableProcesses = $this->workflow->processes()
            ->where('from_status', $this->status)
            ->get();

        return $availableProcesses->filter(function ($process) use ($user) {
            return $this->canTransitionTo($process->to_status, $user);
        })->pluck('to_status')->toArray();
    }

    protected function getProcessForTransition(string $fromStatus, string $toStatus): ?WorkflowProcess
    {
        return $this->workflow->processes()
            ->where('from_status', $fromStatus)
            ->where('to_status', $toStatus)
            ->first();
    }

    protected function logStatusChange(string $fromStatus, string $toStatus, $user = null, ?string $notes = null): void
    {
        WorkflowAuditLog::create([
            'workflowable_type' => get_class($this),
            'workflowable_id' => $this->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'notes' => $notes,
            'metadata' => [
                'ip_address' => function_exists('request') ? request()?->ip() : null,
                'user_agent' => function_exists('request') ? request()?->userAgent() : null,
            ],
        ]);
    }
}
