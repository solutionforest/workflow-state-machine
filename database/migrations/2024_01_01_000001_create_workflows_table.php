<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflows')
            ? config('workflow-state-machine.table_names.workflows')
            : 'workflows';
        $morphName = function_exists('config') && config('workflow-state-machine.morph_name')
            ? config('workflow-state-machine.morph_name')
            : 'workflowable';

        Schema::create($tableName, function (Blueprint $table) use ($morphName) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('starting_status');
            $table->string('ending_status');
            $table->boolean('is_active')->default(true);
            $table->nullableMorphs($morphName);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = function_exists('config') && config('workflow-state-machine.table_names.workflows')
            ? config('workflow-state-machine.table_names.workflows')
            : 'workflows';

        Schema::dropIfExists($tableName);
    }
};
