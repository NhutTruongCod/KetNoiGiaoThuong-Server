<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->foreign('admin_user_id', 'modlogs_admin_fk')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign('modlogs_admin_fk');
        });
    }
};
