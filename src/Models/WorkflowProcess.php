<?php

namespace WorkflowStateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $workflow_id
 * @property string|null $name
 * @property string $from_status
 * @property string $to_status
 * @property int $order
 * @property bool $auto_transition
 * @property bool $completed
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\WorkflowStateMachine\Models\WorkflowRule[] $rules
 */
class WorkflowProcess extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'from_status',
        'to_status',
        'order',
        'auto_transition',
        'completed',
        'description',
    ];

    protected $casts = [
        'auto_transition' => 'boolean',
        'completed' => 'boolean',
        'order' => 'integer',
    ];

    protected $attributes = [
        'completed' => false,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('config') && config('workflow-state-machine.table_names.workflow_processes')) {
            $this->setTable(config('workflow-state-machine.table_names.workflow_processes'));
        } else {
            $this->setTable('workflow_processes');
        }
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function rules(): BelongsToMany
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_process_rules')
            ? config('workflow-state-machine.table_names.workflow_process_rules')
            : 'workflow_process_rules';

        return $this->belongsToMany(
            WorkflowRule::class,
            $tableName,
            'workflow_process_id',
            'workflow_rule_id'
        );
    }

    public function checkRules($model, $user): array
    {
        $results = [];

        foreach ($this->rules as $rule) {
            $ruleInstance = app($rule->rule_class);
            $results[$rule->id] = [
                'rule' => $rule,
                'passed' => $ruleInstance->handle($model, $this, $user),
                'message' => $ruleInstance->getMessage(),
            ];
        }

        return $results;
    }

    public function allRulesPassed($model, $user): bool
    {
        $results = $this->checkRules($model, $user);

        return collect($results)->every(fn ($result) => $result['passed']);
    }
}
