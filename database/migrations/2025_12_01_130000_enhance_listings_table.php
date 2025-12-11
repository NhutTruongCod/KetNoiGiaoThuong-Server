<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Thêm shop_id để liên kết với shops
            $table->unsignedBigInteger('shop_id')->nullable()->after('user_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
            
            // Thêm type để phân loại tin: bán/mua/dịch vụ
            $table->enum('type', ['sell', 'buy', 'service'])->default('sell')->after('category');
            
            // Thêm stock_qty nếu cần quản lý số lượng
            $table->unsignedInteger('stock_qty')->default(0)->after('price_cents');
            
            // Thêm images JSON để lưu mảng URLs ảnh
            $table->json('images')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn(['shop_id', 'type', 'stock_qty', 'images']);
        });
    }
};
