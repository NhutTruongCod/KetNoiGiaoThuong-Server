<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'wallet' to the type enum
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system','order','payment','review','message','listing','shop','promotion','moderation','verification','auction','comment','wallet') DEFAULT 'system'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system','order','payment','review','message','listing','shop','promotion','moderation','verification','auction','comment') DEFAULT 'system'");
    }
};
