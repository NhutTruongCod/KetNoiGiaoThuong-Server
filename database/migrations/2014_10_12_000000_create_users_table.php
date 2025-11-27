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
        // Sử dụng Schema::create thay vì Schema::table
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id'); // Tương đương id() nhưng rõ ràng kiểu dữ liệu
            $table->string('email', 191)->unique('email');
            
            // Lưu ý: Laravel mặc định dùng 'password', bạn dùng 'password_hash' thì phải sửa model User
            $table->string('password_hash'); 
            
            $table->string('full_name', 191);
            $table->string('phone', 32)->nullable();
            $table->string('avatar_url', 512)->nullable();
            
            // Các cột enum và boolean bạn muốn thêm
            $table->enum('role', ['admin', 'seller', 'buyer'])->default('buyer');
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->enum('provider', ['local', 'google', 'facebook'])->default('local');
            $table->string('provider_id', 191)->nullable();
            
            $table->dateTime('last_login_at')->nullable();
            
            // timestamp() tạo ra created_at và updated_at
            $table->timestamps(); 
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
