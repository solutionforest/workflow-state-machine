<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_rules')
            ? config('workflow-state-machine.table_names.workflow_rules')
            : 'workflow_rules';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_class');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflow_rules')
            ? config('workflow-state-machine.table_names.workflow_rules')
            : 'workflow_rules';

        Schema::dropIfExists($tableName);
    }
};
