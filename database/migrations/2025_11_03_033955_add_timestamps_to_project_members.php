<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm created_at và updated_at vào bảng project_members để hỗ trợ withTimestamps() trong relationship
     */
    public function up(): void
    {
        if (Schema::hasTable('project_members')) {
            // Kiểm tra xem đã có cột created_at chưa
            if (!Schema::hasColumn('project_members', 'created_at')) {
                Schema::table('project_members', function (Blueprint $table) {
                    $table->timestamp('created_at')->nullable()->after('joined_at');
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_members')) {
            if (Schema::hasColumn('project_members', 'created_at')) {
                Schema::table('project_members', function (Blueprint $table) {
                    $table->dropColumn(['created_at', 'updated_at']);
                });
            }
        }
    }
};

