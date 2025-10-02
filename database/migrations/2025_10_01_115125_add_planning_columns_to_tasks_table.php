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
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('position')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('tasks', 'wbs_id')) {
                $table->foreignId('wbs_id')->nullable()->after('project_id');
            }

            if (! Schema::hasColumn('tasks', 'planned_start_date')) {
                $table->date('planned_start_date')->nullable()->after('wbs_id');
            }

            if (! Schema::hasColumn('tasks', 'planned_end_date')) {
                $table->date('planned_end_date')->nullable()->after('planned_start_date');
            }

            if (! Schema::hasColumn('tasks', 'actual_start_date')) {
                $table->date('actual_start_date')->nullable()->after('planned_end_date');
            }

            if (! Schema::hasColumn('tasks', 'actual_end_date')) {
                $table->date('actual_end_date')->nullable()->after('actual_start_date');
            }

            if (! Schema::hasColumn('tasks', 'planned_duration_days')) {
                $table->unsignedInteger('planned_duration_days')->nullable()->after('actual_end_date');
            }

            if (! Schema::hasColumn('tasks', 'actual_duration_days')) {
                $table->unsignedInteger('actual_duration_days')->nullable()->after('planned_duration_days');
            }

            if (! Schema::hasColumn('tasks', 'progress_percent')) {
                $table->decimal('progress_percent', 5, 2)->default(0)->after('actual_duration_days');
            }

            if (! Schema::hasColumn('tasks', 'metadata')) {
                $table->json('metadata')->nullable()->after('progress_percent');
            }
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

            if (Schema::hasColumn('tasks', 'project_id')) {
                $table->dropForeign(['project_id']);
            }

            $columns = [
                'metadata',
                'progress_percent',
                'actual_duration_days',
                'planned_duration_days',
                'actual_end_date',
                'actual_start_date',
                'planned_end_date',
                'planned_start_date',
                'wbs_id',
                'project_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
