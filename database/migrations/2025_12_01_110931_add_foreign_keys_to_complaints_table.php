<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreign('complainant_user_id', 'complaints_complainant_fk')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('assigned_admin_id', 'complaints_assigned_admin_fk')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            $table->foreign('resolved_by_admin_id', 'complaints_resolved_admin_fk')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign('complaints_complainant_fk');
            $table->dropForeign('complaints_assigned_admin_fk');
            $table->dropForeign('complaints_resolved_admin_fk');
        });
    }
};
