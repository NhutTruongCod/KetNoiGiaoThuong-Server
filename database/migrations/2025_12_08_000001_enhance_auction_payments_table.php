<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_payments', function (Blueprint $table) {
            // Thông tin giao hàng của người thắng
            $table->string('shipping_name')->nullable()->after('note');
            $table->string('shipping_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('shipping_note')->nullable();
            
            // Thông tin liên hệ seller (hiển thị sau khi thanh toán)
            $table->string('seller_phone')->nullable();
            $table->string('seller_email')->nullable();
            
            // Phương thức thanh toán thay thế
            $table->enum('payment_method', ['wallet', 'bank_transfer'])->default('wallet');
            $table->string('bank_transfer_proof')->nullable(); // Ảnh chứng từ chuyển khoản
            $table->timestamp('bank_transfer_confirmed_at')->nullable();
            $table->foreignId('bank_transfer_confirmed_by')->nullable()->constrained('users');
            
            // Admin note
            $table->text('admin_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('auction_payments', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_name',
                'shipping_phone', 
                'shipping_address',
                'shipping_note',
                'seller_phone',
                'seller_email',
                'payment_method',
                'bank_transfer_proof',
                'bank_transfer_confirmed_at',
                'bank_transfer_confirmed_by',
                'admin_note',
            ]);
        });
    }
};
