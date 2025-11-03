<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo lại tất cả các bảng đã bị xóa bởi migration fix
     */
    public function up(): void
    {
        // Tạo bảng sprints nếu chưa có
        if (!Schema::hasTable('sprints')) {
            Schema::create('sprints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->string('name', 255);
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
                $table->timestamps();

                // Indexes
                $table->index('project_id');
                $table->index('status');
                $table->index(['start_date', 'end_date']);
            });
        }

        // Tạo bảng tasks nếu chưa có
        if (!Schema::hasTable('tasks')) {
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

        // Tạo bảng task_assets nếu chưa có
        if (!Schema::hasTable('task_assets')) {
            Schema::create('task_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
                $table->string('image_url', 500);
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
                $table->timestamp('uploaded_at')->useCurrent();

                // Indexes
                $table->index('task_id');
            });
        }

        // Tạo bảng sub_tasks nếu chưa có
        if (!Schema::hasTable('sub_tasks')) {
            Schema::create('sub_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
                $table->string('title', 255);
                $table->date('date')->nullable();
                $table->string('tag', 100)->nullable();
                $table->boolean('is_completed')->default(false);
                $table->timestamps();

                // Indexes
                $table->index('task_id');
            });
        }

        // Tạo bảng task_activities nếu chưa có
        if (!Schema::hasTable('task_activities')) {
            Schema::create('task_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
                $table->enum('type', [
                    'assigned',
                    'started',
                    'in_progress',
                    'completed',
                    'commented',
                    'bug',
                    'asset_added',
                    'status_changed',
                    'subtask_created'
                ]);
                $table->text('content')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('task_id');
                $table->index('created_at');
                $table->index('type');
            });
        }

        // Tạo bảng notifications nếu chưa có
        if (!Schema::hasTable('notifications')) {
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
                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index('is_read');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không xóa bảng vì đây là migration để tạo lại các bảng đã bị xóa
    }
};

