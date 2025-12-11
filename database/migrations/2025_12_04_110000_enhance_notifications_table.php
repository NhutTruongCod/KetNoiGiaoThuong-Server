<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Change type enum to include all types
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system', 'order', 'payment', 'review', 'message', 'listing', 'shop', 'promotion', 'moderation', 'verification') DEFAULT 'system'");
            
            // Add new columns
            $table->json('data')->nullable()->after('message');
            $table->string('action_url')->nullable()->after('data');
            $table->string('action_text')->nullable()->after('action_url');
            $table->string('icon', 50)->nullable()->after('action_text');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal')->after('icon');
            $table->timestamp('read_at')->nullable()->after('is_read');
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('system', 'order', 'promotion', 'moderation') DEFAULT 'system'");
            
            $table->dropColumn([
                'data',
                'action_url',
                'action_text',
                'icon',
                'priority',
                'read_at'
            ]);
        });
    }
};
