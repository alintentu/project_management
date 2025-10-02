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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('backlog');
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wbs_id')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->unsignedInteger('planned_duration_days')->nullable();
            $table->unsignedInteger('actual_duration_days')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['status', 'position']);
            $table->index(['project_id', 'wbs_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
