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
        Schema::create('task_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->timestamp('occurred_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['task_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_status_transitions');
    }
};
