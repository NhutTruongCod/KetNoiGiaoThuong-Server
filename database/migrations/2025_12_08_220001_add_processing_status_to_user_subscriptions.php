<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify enum to add 'processing' and 'rejected' status
        DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('pending', 'processing', 'active', 'expired', 'cancelled', 'rejected') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending'");
    }
};
