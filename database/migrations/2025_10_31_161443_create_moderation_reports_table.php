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
        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reporter_id')->index('fk_mod_reporter');
            $table->unsignedBigInteger('target_user_id')->nullable()->index('fk_mod_target_user');
            $table->unsignedBigInteger('target_post_id')->nullable()->index('fk_mod_target_post');
            $table->string('reason');
            $table->enum('status', ['pending', 'reviewed', 'action_taken', 'dismissed'])->nullable()->default('pending');
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->index(['status', 'created_at'], 'idx_mod_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moderation_reports');
    }
};
