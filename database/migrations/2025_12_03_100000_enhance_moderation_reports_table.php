<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('moderation_reports', function (Blueprint $table) {
            // Thêm reportable type và ID (polymorphic)
            $table->string('reportable_type', 50)->nullable()->after('reporter_id');
            $table->unsignedBigInteger('reportable_id')->nullable()->after('reportable_type');
            
            // Thêm description và evidence
            $table->text('description')->nullable()->after('reason');
            $table->json('evidence_images')->nullable()->after('description');
            
            // Thêm resolution và action_taken
            $table->string('resolution', 50)->nullable()->after('status');
            $table->string('action_taken', 50)->nullable()->after('resolution');
            $table->text('admin_notes')->nullable()->after('action_taken');
            
            // Thêm updated_at
            $table->timestamp('updated_at')->nullable()->after('reviewed_at');
            
            // Thêm index cho reportable
            $table->index(['reportable_type', 'reportable_id'], 'idx_reportable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('moderation_reports', function (Blueprint $table) {
            $table->dropIndex('idx_reportable');
            $table->dropColumn([
                'reportable_type',
                'reportable_id',
                'description',
                'evidence_images',
                'resolution',
                'action_taken',
                'admin_notes',
                'updated_at'
            ]);
        });
    }
};
