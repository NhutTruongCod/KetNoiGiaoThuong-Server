<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm composite index cho user_id + title để tối ưu việc kiểm tra trùng tên bài đăng
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Index để tối ưu query kiểm tra trùng title theo user
            $table->index(['user_id', 'title'], 'listings_user_title_index');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_user_title_index');
        });
    }
};
