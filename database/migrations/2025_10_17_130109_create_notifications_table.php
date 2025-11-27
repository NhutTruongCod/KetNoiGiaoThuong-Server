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
        // Ghi đè hoàn toàn bảng notifications theo cấu trúc mới
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title', 191);
            $table->text('message');
            $table->enum('type', ['system', 'order', 'promotion', 'moderation'])->default('system');
            $table->boolean('is_read')->default(false);
            
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent(); // Nên thêm cái này để chuẩn Laravel

            $table->index(['user_id', 'is_read'], 'idx_notifications_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
