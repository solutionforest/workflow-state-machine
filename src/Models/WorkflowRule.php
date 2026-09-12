<?php

namespace WorkflowStateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $rule_class
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WorkflowRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rule_class',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('config') && config('workflow-state-machine.table_names.workflow_rules')) {
            $this->setTable(config('workflow-state-machine.table_names.workflow_rules'));
        } else {
            $this->setTable('workflow_rules');
        }
    }

    public function processes(): BelongsToMany
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_process_rules')
            ? config('workflow-state-machine.table_names.workflow_process_rules')
            : 'workflow_process_rules';

        return $this->belongsToMany(
            WorkflowProcess::class,
            $tableName,
            'workflow_rule_id',
            'workflow_process_id'
        );
    }
}
