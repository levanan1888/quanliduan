<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kiểm tra xem bảng có tồn tại không
        if (!Schema::hasTable('projects')) {
            // Nếu không tồn tại, tạo mới
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'archived', 'completed'])->default('active');
                $table->foreignId('manager_id')->constrained('users')->onDelete('restrict');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('manager_id');
                $table->index('status');
            });
            return;
        }

        // Nếu bảng đã tồn tại, kiểm tra và thêm cột 'name' nếu chưa có
        if (!Schema::hasColumn('projects', 'name')) {
            // Tạm thời tắt foreign key checks để có thể drop bảng
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Drop các bảng phụ thuộc trước
            if (Schema::hasTable('project_members')) {
                Schema::dropIfExists('project_members');
            }
            if (Schema::hasTable('sprints')) {
                Schema::dropIfExists('sprints');
            }
            if (Schema::hasTable('tasks')) {
                // Drop các bảng phụ thuộc của tasks trước
                if (Schema::hasTable('task_assets')) {
                    Schema::dropIfExists('task_assets');
                }
                if (Schema::hasTable('sub_tasks')) {
                    Schema::dropIfExists('sub_tasks');
                }
                if (Schema::hasTable('task_activities')) {
                    Schema::dropIfExists('task_activities');
                }
                Schema::dropIfExists('tasks');
            }
            
            // Drop bảng projects
            Schema::dropIfExists('projects');
            
            // Tạo lại bảng projects
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'archived', 'completed'])->default('active');
                $table->foreignId('manager_id')->constrained('users')->onDelete('restrict');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('manager_id');
                $table->index('status');
            });
            
            // Tạo lại bảng project_members (bị xóa ở trên)
            if (!Schema::hasTable('project_members')) {
                Schema::create('project_members', function (Blueprint $table) {
                    $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                    $table->timestamp('joined_at')->useCurrent();
                    $table->timestamps(); // Add created_at and updated_at for withTimestamps() in relationship

                    $table->primary(['project_id', 'user_id']);
                    $table->index('user_id');
                });
            }
            
            // Bật lại foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không làm gì, để migration gốc xử lý
    }
};

