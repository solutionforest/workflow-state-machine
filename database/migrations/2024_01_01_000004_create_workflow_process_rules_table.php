<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_process_rules')
            ? config('workflow-state-machine.table_names.workflow_process_rules')
            : 'workflow_process_rules';
        $processesTable = function_exists('config') && config('workflow-state-machine.table_names.workflow_processes')
            ? config('workflow-state-machine.table_names.workflow_processes')
            : 'workflow_processes';
        $rulesTable = function_exists('config') && config('workflow-state-machine.table_names.workflow_rules')
            ? config('workflow-state-machine.table_names.workflow_rules')
            : 'workflow_rules';

        Schema::create($tableName, function (Blueprint $table) use ($processesTable, $rulesTable) {
            $table->id();
            $table->foreignId('workflow_process_id')->constrained($processesTable)->onDelete('cascade');
            $table->foreignId('workflow_rule_id')->constrained($rulesTable)->onDelete('cascade');
            $table->timestamps();

            $table->unique(['workflow_process_id', 'workflow_rule_id'], 'wp_wr_unique');
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_process_rules')
            ? config('workflow-state-machine.table_names.workflow_process_rules')
            : 'workflow_process_rules';

        Schema::dropIfExists($tableName);
    }
};
