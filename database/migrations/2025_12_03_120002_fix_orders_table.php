<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check and add columns if they don't exist
        $columns = Schema::getColumnListing('orders');
        
        Schema::table('orders', function (Blueprint $table) use ($columns) {
            if (!in_array('order_number', $columns)) {
                $table->string('order_number', 50)->nullable()->after('id');
            }
            if (!in_array('listing_id', $columns)) {
                $table->unsignedBigInteger('listing_id')->nullable()->after('shop_id');
            }
            if (!in_array('seller_id', $columns)) {
                $table->unsignedBigInteger('seller_id')->nullable()->after('listing_id');
            }
            if (!in_array('quantity', $columns)) {
                $table->integer('quantity')->default(1)->after('seller_id');
            }
            if (!in_array('unit_price', $columns)) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('quantity');
            }
            if (!in_array('shipping_fee', $columns)) {
                $table->decimal('shipping_fee', 12, 2)->default(0)->after('total_amount');
            }
            if (!in_array('discount_amount', $columns)) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('shipping_fee');
            }
            if (!in_array('tax_amount', $columns)) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
            }
            if (!in_array('final_amount', $columns)) {
                $table->decimal('final_amount', 12, 2)->default(0)->after('tax_amount');
            }
            if (!in_array('payment_method', $columns)) {
                $table->enum('payment_method', ['cod', 'vnpay', 'momo', 'bank_transfer'])->default('cod')->after('status');
            }
            if (!in_array('payment_status', $columns)) {
                $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid')->after('payment_method');
            }
            if (!in_array('shipping_address', $columns)) {
                $table->json('shipping_address')->nullable()->after('payment_status');
            }
            if (!in_array('note', $columns)) {
                $table->text('note')->nullable()->after('shipping_address');
            }
            if (!in_array('tracking_number', $columns)) {
                $table->string('tracking_number', 100)->nullable()->after('note');
            }
            if (!in_array('shipped_at', $columns)) {
                $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            }
            if (!in_array('delivered_at', $columns)) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (!in_array('cancelled_at', $columns)) {
                $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            }
            if (!in_array('cancel_reason', $columns)) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }
            if (!in_array('coupon_code', $columns)) {
                $table->string('coupon_code', 50)->nullable()->after('cancel_reason');
            }
        });
        
        // Update status enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'shipping', 'delivered', 'completed', 'cancelled', 'refunded') DEFAULT 'pending'");
        
        // Generate order numbers for existing orders without one
        $existingOrders = DB::table('orders')->whereNull('order_number')->orWhere('order_number', '')->get();
        foreach ($existingOrders as $index => $order) {
            $orderNumber = 'ORD-' . date('Ymd', strtotime($order->created_at)) . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            DB::table('orders')
                ->where('id', $order->id)
                ->update(['order_number' => $orderNumber]);
        }
        
        // Add indexes and foreign keys - silently skip if they exist
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('listing_id', 'idx_order_listing');
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('seller_id', 'idx_order_seller');
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('payment_status', 'idx_payment_status');
            });
        } catch (\Exception $e) {
            // Index already exists
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('listing_id', 'fk_orders_listing')->references('id')->on('listings')->onDelete('SET NULL');
            });
        } catch (\Exception $e) {
            // Foreign key already exists
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('seller_id', 'fk_orders_seller')->references('id')->on('users')->onDelete('SET NULL');
            });
        } catch (\Exception $e) {
            // Foreign key already exists
        }
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('order_number', 'orders_order_number_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint already exists
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            try { $table->dropUnique('orders_order_number_unique'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_orders_listing'); } catch (\Exception $e) {}
            try { $table->dropForeign('fk_orders_seller'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_order_listing'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_order_seller'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payment_status'); } catch (\Exception $e) {}
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
