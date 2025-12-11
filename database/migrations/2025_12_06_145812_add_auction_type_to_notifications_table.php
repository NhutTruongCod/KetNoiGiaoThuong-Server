<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm type 'auction' và 'comment' vào notifications
     */
    public function up(): void
    {
        // Thêm 'auction' và 'comment' vào enum type
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system', 'order', 'payment', 'review', 'message', 'listing', 'shop', 'promotion', 'moderation', 'verification', 'auction', 'comment') DEFAULT 'system'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system', 'order', 'payment', 'review', 'message', 'listing', 'shop', 'promotion', 'moderation', 'verification') DEFAULT 'system'");
    }
};
