<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sử dụng create để tạo bảng mới
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            
            // --- KHÓA NGOẠI & LIÊN KẾT ---
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            

            // --- THÔNG TIN CƠ BẢN ---
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            
            $table->string('category', 100)->nullable(); 
            
            // Ưu tiên price_cents (BigInt) của đoạn 1 để tránh lỗi làm tròn tiền tệ
            $table->bigInteger('price_cents')->default(0);
            $table->string('currency', 10)->default('VND');

            // --- ĐỊA ĐIỂM (Lấy từ đoạn 1) ---
            $table->string('location_text')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            
            $table->string('status', 20)->default('draft'); // draft|published|archived
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true); 

            $table->json('meta')->nullable();
            $table->timestamps();

            
            $table->index('title'); 
            $table->index(['category', 'status', 'is_public']); 
            $table->index(['latitude', 'longitude']); 
            $table->index('is_active'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};