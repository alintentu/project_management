<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_flow_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamp('captured_at')->index();
            $table->json('summary');
            $table->json('alerts')->nullable();
            $table->string('focus')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_flow_snapshots');
    }
};
