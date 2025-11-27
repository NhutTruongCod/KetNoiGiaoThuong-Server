<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->enum('status', ['active', 'inactive', 'expired', 'upcoming'])->default('upcoming');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('discount_percentage', 5, 2)->nullable(); // 100.00%
            $table->decimal('min_order_amount', 10, 2)->nullable(); // Đơn hàng tối thiểu
            $table->integer('max_usage')->nullable(); // Số lần sử dụng tối đa
            $table->string('promo_code')->unique()->nullable(); // Mã khuyến mãi
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
          
            // Indexes for performance
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('promo_code');
            $table->index('is_featured');
            $table->index(['status', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};