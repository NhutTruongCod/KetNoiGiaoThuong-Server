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
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->enum('format', ['csv', 'json'])->default('json');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->nullable()->default('pending');
            $table->string('download_url', 512)->nullable();
            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('completed_at')->nullable();

            $table->index(['user_id', 'status'], 'idx_data_export_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_export_requests');
    }
};
