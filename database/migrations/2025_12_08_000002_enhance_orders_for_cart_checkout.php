<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('orders');
        
        Schema::table('orders', function (Blueprint $table) use ($columns) {
            // Loại sản phẩm: physical (vật lý) hoặc digital (số)
            if (!in_array('product_type', $columns)) {
                $table->enum('product_type', ['physical', 'digital'])->default('physical')->after('listing_id');
            }
            
            // Trạng thái trao đổi (cho sản phẩm số)
            if (!in_array('chat_confirmed', $columns)) {
                $table->boolean('chat_confirmed')->default(false)->after('product_type');
            }
            if (!in_array('chat_confirmed_at', $columns)) {
                $table->timestamp('chat_confirmed_at')->nullable()->after('chat_confirmed');
            }
            
            // Thông tin thanh toán qua ví
            if (!in_array('wallet_transaction_id', $columns)) {
                $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('payment_status');
            }
            if (!in_array('paid_at', $columns)) {
                $table->timestamp('paid_at')->nullable()->after('wallet_transaction_id');
            }
            
            // Thông tin seller nhận tiền
            if (!in_array('seller_received_at', $columns)) {
                $table->timestamp('seller_received_at')->nullable()->after('paid_at');
            }
            if (!in_array('seller_wallet_transaction_id', $columns)) {
                $table->unsignedBigInteger('seller_wallet_transaction_id')->nullable()->after('seller_received_at');
            }
            
            // Platform fee
            if (!in_array('platform_fee', $columns)) {
                $table->decimal('platform_fee', 12, 2)->default(0)->after('tax_amount');
            }
            if (!in_array('seller_receive', $columns)) {
                $table->decimal('seller_receive', 12, 2)->default(0)->after('platform_fee');
            }
            
            // Thông tin liên hệ sau thanh toán
            if (!in_array('seller_contact', $columns)) {
                $table->json('seller_contact')->nullable()->after('shipping_address');
            }
            if (!in_array('buyer_contact', $columns)) {
                $table->json('buyer_contact')->nullable()->after('seller_contact');
            }
        });
        
        // Update payment_method enum để thêm 'wallet'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cod', 'vnpay', 'momo', 'bank_transfer', 'wallet') DEFAULT 'wallet'");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'chat_confirmed',
                'chat_confirmed_at',
                'wallet_transaction_id',
                'paid_at',
                'seller_received_at',
                'seller_wallet_transaction_id',
                'platform_fee',
                'seller_receive',
                'seller_contact',
                'buyer_contact',
            ]);
        });
        
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cod', 'vnpay', 'momo', 'bank_transfer') DEFAULT 'cod'");
    }
};
