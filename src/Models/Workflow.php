<?php

namespace WorkflowStateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use WorkflowStateMachine\Events\WorkflowCreated;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $starting_status
 * @property string $ending_status
 * @property bool $is_active
 * @property string|null $workflowable_type
 * @property int|null $workflowable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Workflow extends Model
{
    protected $fillable = [
        'name',
        'description',
        'starting_status',
        'ending_status',
        'is_active',
        'workflowable_type',
        'workflowable_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'created' => WorkflowCreated::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('config') && config('workflow-state-machine.table_names.workflows')) {
            $this->setTable(config('workflow-state-machine.table_names.workflows'));
        } else {
            $this->setTable('workflows');
        }
    }

    public function processes(): HasMany
    {
        return $this->hasMany(WorkflowProcess::class)->orderBy('order');
    }

    public function workflowable(): MorphTo
    {
        $morphName = function_exists('config') && config('workflow-state-machine.morph_name')
            ? config('workflow-state-machine.morph_name')
            : 'workflowable';

        return $this->morphTo($morphName);
    }

    public function getStatusRoadmap(): array
    {
        return $this->processes()
            ->orderBy('order')
            ->pluck('to_status')
            ->prepend($this->starting_status)
            ->unique()
            ->values()
            ->toArray();
    }

    public function getNextStatus(string $currentStatus): ?string
    {
        $roadmap = $this->getStatusRoadmap();
        $currentIndex = array_search($currentStatus, $roadmap);

        if ($currentIndex === false || $currentIndex >= count($roadmap) - 1) {
            return null;
        }

        return $roadmap[$currentIndex + 1];
    }

    public function getPreviousStatus(string $currentStatus): ?string
    {
        $roadmap = $this->getStatusRoadmap();
        $currentIndex = array_search($currentStatus, $roadmap);

        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }

        return $roadmap[$currentIndex - 1];
    }

    public function canTransitionTo(string $fromStatus, string $toStatus): bool
    {
        return $this->processes()
            ->where('from_status', $fromStatus)
            ->where('to_status', $toStatus)
            ->exists();
    }
}
