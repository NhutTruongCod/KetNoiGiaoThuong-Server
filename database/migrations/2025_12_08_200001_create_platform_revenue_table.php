<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng doanh thu platform
        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->enum('type', [
                'withdraw_fee',      // Phí rút tiền
                'auction_fee',       // Phí đấu giá (platform_fee)
                'order_fee',         // Phí giao dịch đơn hàng
                'promotion_fee',     // Phí quảng cáo
                'subscription_fee',  // Phí gói đăng ký
                'other',             // Khác
            ]);
            $table->decimal('amount', 20, 2); // Tổng số tiền thu
            $table->decimal('vat_amount', 20, 2)->default(0); // Thuế VAT (10%)
            $table->decimal('net_amount', 20, 2); // Số tiền sau thuế
            $table->string('reference_type')->nullable(); // withdraw_request, auction_payment, order, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // User liên quan
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Thêm các cột thuế vào withdraw_requests
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->decimal('vat_fee', 20, 2)->default(0)->after('fee'); // Thuế VAT 10%
            $table->decimal('pit_fee', 20, 2)->default(0)->after('vat_fee'); // Thuế TNCN (Personal Income Tax)
            $table->decimal('total_fee', 20, 2)->default(0)->after('pit_fee'); // Tổng phí
        });

        // Thêm cột để track platform revenue từ auction
        Schema::table('auction_payments', function (Blueprint $table) {
            $table->decimal('vat_fee', 20, 2)->default(0)->after('platform_fee'); // VAT trên phí sàn
        });
    }

    public function down(): void
    {
        Schema::table('auction_payments', function (Blueprint $table) {
            $table->dropColumn('vat_fee');
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropColumn(['vat_fee', 'pit_fee', 'total_fee']);
        });

        Schema::dropIfExists('platform_revenues');
    }
};
