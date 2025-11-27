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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Khóa ngoại trỏ về users
            $table->string('otp_code');
            $table->string('type')->default('email_verification'); // Loại OTP
            $table->timestamp('expire_at')->nullable(); // Thời gian hết hạn
            $table->boolean('is_used')->default(false); // Đã dùng chưa
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Tạo liên kết khóa ngoại (quan trọng)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    
    public function down()
    {
        Schema::dropIfExists('otp_codes');
    }

    
};
