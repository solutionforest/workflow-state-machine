<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_processes')
            ? config('workflow-state-machine.table_names.workflow_processes')
            : 'workflow_processes';
        $workflowsTable = function_exists('config') && config('workflow-state-machine.table_names.workflows')
            ? config('workflow-state-machine.table_names.workflows')
            : 'workflows';

        Schema::create($tableName, function (Blueprint $table) use ($workflowsTable) {
            $table->id();
            $table->foreignId('workflow_id')->constrained($workflowsTable)->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('from_status');
            $table->string('to_status');
            $table->integer('order')->default(0);
            $table->boolean('auto_transition')->default(false);
            $table->boolean('completed')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'from_status', 'to_status']);
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_processes')
            ? config('workflow-state-machine.table_names.workflow_processes')
            : 'workflow_processes';

        Schema::dropIfExists($tableName);
    }
};
