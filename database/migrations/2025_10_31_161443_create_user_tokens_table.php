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
        Schema::create('user_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->char('token', 64)->unique('token');
            $table->enum('type', ['refresh', 'password_reset', 'email_verify', '2fa']);
            $table->dateTime('expires_at');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['user_id', 'type'], 'idx_user_tokens_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_tokens');
    }
};
