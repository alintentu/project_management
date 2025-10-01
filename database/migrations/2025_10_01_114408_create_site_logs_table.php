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
        Schema::create('site_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->date('log_date');
            $table->string('weather')->nullable();
            $table->string('temperature')->nullable();
            $table->unsignedInteger('manpower_count')->nullable();
            $table->decimal('progress_percent', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->text('issues')->nullable();
            $table->boolean('safety_incident_occurred')->default(false);
            $table->json('metrics')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_logs');
    }
};
