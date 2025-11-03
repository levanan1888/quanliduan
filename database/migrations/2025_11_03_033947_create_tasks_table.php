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
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->onDelete('set null');
            $table->string('title', 255);
            $table->date('date')->nullable();
            $table->enum('priority', ['HIGH', 'MEDIUM', 'LOW'])->default('MEDIUM');
            $table->enum('status', ['TO_DO', 'IN_PROGRESS', 'COMPLETED'])->default('TO_DO');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            // Indexes
            $table->index('project_id');
            $table->index('sprint_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
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

