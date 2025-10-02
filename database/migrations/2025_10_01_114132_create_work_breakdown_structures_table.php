<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_breakdown_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('work_breakdown_structures')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('phase_type')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->text('description')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'parent_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'wbs_id')) {
                $table->foreign('wbs_id')
                    ->references('id')
                    ->on('work_breakdown_structures')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'wbs_id')) {
                $table->dropForeign(['wbs_id']);
            }
        });
        Schema::dropIfExists('work_breakdown_structures');
    }
};
