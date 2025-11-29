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
        
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 191)->unique('email');
            $table->string('password_hash');
            $table->string('full_name', 191);
            $table->string('phone', 32)->nullable();
            $table->string('avatar_url', 512)->nullable();
            $table->enum('role', ['admin', 'seller', 'buyer'])->default('buyer');
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('provider', ['local', 'google', 'facebook'])->default('local');
            $table->string('provider_id', 191)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
