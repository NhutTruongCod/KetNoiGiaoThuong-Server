<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = Schema::getColumnListing('payments');
            
            // Thêm user_id
            if (!in_array('user_id', $columns)) {
                $table->unsignedBigInteger('user_id')->after('id');
            }
            
            // Polymorphic relationship - QUAN TRỌNG!
            if (!in_array('payable_type', $columns)) {
                $table->string('payable_type')->nullable()->after('order_id');
            }
            if (!in_array('payable_id', $columns)) {
                $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            }
            
            // Payment type
            if (!in_array('payment_type', $columns)) {
                $table->enum('payment_type', [
                    'order',        // Thanh toán đơn hàng
                    'subscription', // Thanh toán gói VIP
                    'promotion',    // Thanh toán quảng cáo
                    'auction',      // Thanh toán đấu giá
                    'fee'          // Phí giao dịch
                ])->default('order')->after('payable_id');
            }
            
            // Payer & Payee
            if (!in_array('payer_id', $columns)) {
                $table->unsignedBigInteger('payer_id')->nullable()->after('user_id');
            }
            if (!in_array('payee_id', $columns)) {
                $table->unsignedBigInteger('payee_id')->nullable()->after('payer_id');
            }
            
            // Payment details
            if (!in_array('currency', $columns)) {
                $table->string('currency', 3)->default('VND')->after('amount');
            }
            if (!in_array('payment_gateway', $columns)) {
                $table->string('payment_gateway')->nullable()->after('method');
            }
            if (!in_array('payment_gateway_response', $columns)) {
                $table->json('payment_gateway_response')->nullable()->after('payment_gateway');
            }
            if (!in_array('return_url', $columns)) {
                $table->string('return_url')->nullable()->after('payment_gateway_response');
            }
            if (!in_array('transaction_id', $columns)) {
                $table->string('transaction_id')->nullable()->after('return_url');
            }
            if (!in_array('description', $columns)) {
                $table->text('description')->nullable()->after('transaction_id');
            }
            if (!in_array('metadata', $columns)) {
                $table->json('metadata')->nullable()->after('description');
            }
            if (!in_array('updated_at', $columns)) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        
        // Sửa enum status nếu cần
        try {
            \DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Nếu lỗi thì bỏ qua (có thể đã có sẵn)
        }
        
        // Thêm indexes
        Schema::table('payments', function (Blueprint $table) {
            $indexes = \DB::select("SHOW INDEX FROM payments");
            $indexNames = array_column($indexes, 'Key_name');
            
            if (!in_array('payments_user_id_index', $indexNames)) {
                $table->index('user_id');
            }
            if (!in_array('payments_payer_id_index', $indexNames)) {
                $table->index('payer_id');
            }
            if (!in_array('payments_payee_id_index', $indexNames)) {
                $table->index('payee_id');
            }
            if (!in_array('payments_payable_type_payable_id_index', $indexNames)) {
                $table->index(['payable_type', 'payable_id']);
            }
            if (!in_array('payments_payment_type_index', $indexNames)) {
                $table->index('payment_type');
            }
            if (!in_array('payments_transaction_id_index', $indexNames)) {
                $table->index('transaction_id');
            }
            if (!in_array('payments_payment_gateway_index', $indexNames)) {
                $table->index('payment_gateway');
            }
        });
        
        // Thêm foreign keys
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('user_id', 'fk_payments_user')->references('id')->on('users')->onDelete('CASCADE');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('payer_id', 'fk_payments_payer')->references('id')->on('users')->onDelete('CASCADE');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('payee_id', 'fk_payments_payee')->references('id')->on('users')->onDelete('SET NULL');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign keys
            try { $table->dropForeign('fk_payments_user'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_payments_payer'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_payments_payee'); } catch (\Exception $e) {}
            
            // Drop indexes
            try { $table->dropIndex('payments_user_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_payer_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_payee_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_payable_type_payable_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_payment_type_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_transaction_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('payments_payment_gateway_index'); } catch (\Exception $e) {}
            
            // Drop columns
            $columns = Schema::getColumnListing('payments');
            $dropColumns = [];
            
            if (in_array('user_id', $columns)) $dropColumns[] = 'user_id';
            if (in_array('payable_type', $columns)) $dropColumns[] = 'payable_type';
            if (in_array('payable_id', $columns)) $dropColumns[] = 'payable_id';
            if (in_array('payment_type', $columns)) $dropColumns[] = 'payment_type';
            if (in_array('payer_id', $columns)) $dropColumns[] = 'payer_id';
            if (in_array('payee_id', $columns)) $dropColumns[] = 'payee_id';
            if (in_array('currency', $columns)) $dropColumns[] = 'currency';
            if (in_array('payment_gateway', $columns)) $dropColumns[] = 'payment_gateway';
            if (in_array('payment_gateway_response', $columns)) $dropColumns[] = 'payment_gateway_response';
            if (in_array('return_url', $columns)) $dropColumns[] = 'return_url';
            if (in_array('transaction_id', $columns)) $dropColumns[] = 'transaction_id';
            if (in_array('description', $columns)) $dropColumns[] = 'description';
            if (in_array('metadata', $columns)) $dropColumns[] = 'metadata';
            if (in_array('updated_at', $columns)) $dropColumns[] = 'updated_at';
            
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
