<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Rename columns first
        Schema::table('login_history', function (Blueprint $table) {
            $table->renameColumn('success', 'is_successful');
            $table->renameColumn('logged_in_at', 'login_at');
        });
        
        // Step 2: Add new columns after rename
        Schema::table('login_history', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('user_agent');
            $table->string('browser', 100)->nullable()->after('device_type');
            $table->string('os', 100)->nullable()->after('browser');
            $table->json('location')->nullable()->after('os');
            $table->timestamp('logout_at')->nullable()->after('login_at');
            $table->integer('session_duration')->nullable()->after('logout_at')->comment('Duration in seconds');
            $table->string('failure_reason')->nullable()->after('is_successful');
            $table->timestamps();
        });
    }

    public function down()
    {
        // Step 1: Drop added columns first
        Schema::table('login_history', function (Blueprint $table) {
            $table->dropColumn([
                'device_type',
                'browser',
                'os',
                'location',
                'logout_at',
                'session_duration',
                'failure_reason',
                'created_at',
                'updated_at'
            ]);
        });
        
        // Step 2: Rename columns back
        Schema::table('login_history', function (Blueprint $table) {
            $table->renameColumn('is_successful', 'success');
            $table->renameColumn('login_at', 'logged_in_at');
        });
    }
};
