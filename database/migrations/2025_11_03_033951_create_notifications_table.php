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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->enum('type', [
                'task_assigned',
                'status_changed',
                'comment',
                'mention',
                'deadline'
            ])->default('task_assigned');
            $table->foreignId('related_task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->foreignId('related_project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('user_id');
            $table->index('is_read');
            $table->index(['created_at'], 'idx_created_at_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

