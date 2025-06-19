<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_audit_logs')
            ? config('workflow-state-machine.table_names.workflow_audit_logs')
            : 'workflow_audit_logs';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('workflowable');
            $table->string('from_status');
            $table->string('to_status');
            $table->nullableMorphs('user');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_audit_logs')
            ? config('workflow-state-machine.table_names.workflow_audit_logs')
            : 'workflow_audit_logs';

        Schema::dropIfExists($tableName);
    }
};
