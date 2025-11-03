<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng project_members nếu chưa tồn tại (để fix lỗi migration fix đã xóa bảng này)
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};

