<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Update enum values
        DB::statement("ALTER TABLE data_export_requests MODIFY COLUMN format ENUM('csv', 'json', 'xlsx') DEFAULT 'json'");
        DB::statement("ALTER TABLE data_export_requests MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed', 'expired', 'cancelled') DEFAULT 'pending'");
        
        // Step 2: Add new columns
        Schema::table('data_export_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('data_export_requests', 'data_types')) {
                $table->json('data_types')->nullable()->after('format');
            }
            if (!Schema::hasColumn('data_export_requests', 'date_from')) {
                $table->date('date_from')->nullable()->after('data_types');
            }
            if (!Schema::hasColumn('data_export_requests', 'date_to')) {
                $table->date('date_to')->nullable()->after('date_from');
            }
            if (!Schema::hasColumn('data_export_requests', 'include_deleted')) {
                $table->boolean('include_deleted')->default(false)->after('date_to');
            }
            if (!Schema::hasColumn('data_export_requests', 'progress')) {
                $table->integer('progress')->default(0)->after('status');
            }
            if (!Schema::hasColumn('data_export_requests', 'current_step')) {
                $table->string('current_step')->nullable()->after('progress');
            }
            if (!Schema::hasColumn('data_export_requests', 'file_name')) {
                $table->string('file_name')->nullable()->after('download_url');
            }
            if (!Schema::hasColumn('data_export_requests', 'file_size')) {
                $table->bigInteger('file_size')->nullable()->after('file_name');
            }
            if (!Schema::hasColumn('data_export_requests', 'downloads_count')) {
                $table->integer('downloads_count')->default(0)->after('file_size');
            }
            if (!Schema::hasColumn('data_export_requests', 'max_downloads')) {
                $table->integer('max_downloads')->default(5)->after('downloads_count');
            }
            if (!Schema::hasColumn('data_export_requests', 'estimated_completion')) {
                $table->timestamp('estimated_completion')->nullable()->after('requested_at');
            }
            if (!Schema::hasColumn('data_export_requests', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('data_export_requests', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('data_export_requests', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('expired_at');
            }
            if (!Schema::hasColumn('data_export_requests', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        DB::statement("ALTER TABLE data_export_requests MODIFY COLUMN format ENUM('csv', 'json') DEFAULT 'json'");
        DB::statement("ALTER TABLE data_export_requests MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'");
        
        Schema::table('data_export_requests', function (Blueprint $table) {
            $table->dropColumn([
                'data_types',
                'date_from',
                'date_to',
                'include_deleted',
                'progress',
                'current_step',
                'file_name',
                'file_size',
                'downloads_count',
                'max_downloads',
                'estimated_completion',
                'expires_at',
                'expired_at',
                'cancelled_at',
                'created_at',
                'updated_at'
            ]);
        });
    }
};
