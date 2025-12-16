<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Thêm các trường vận chuyển và xác nhận nhận hàng
     * 
     * Trạng thái đơn hàng theo UI:
     * - pending: Chờ thanh toán
     * - confirmed: Đã xác nhận (seller xác nhận đơn)
     * - processing: Đang chuẩn bị hàng
     * - shipping: Đang giao hàng
     * - delivered: Đã giao hàng (shipper giao xong)
     * - completed: Hoàn thành (buyer xác nhận nhận hàng + chụp ảnh)
     * - cancelled: Đã hủy
     * - refunded: Đã hoàn tiền
     */
    public function up(): void
    {
        $columns = Schema::getColumnListing('orders');
        
        Schema::table('orders', function (Blueprint $table) use ($columns) {
            // === THÔNG TIN VẬN CHUYỂN ===
            // Đơn vị vận chuyển
            if (!in_array('shipping_carrier', $columns)) {
                $table->string('shipping_carrier', 100)->nullable()->after('tracking_number')
                    ->comment('Đơn vị vận chuyển: ghn, ghtk, viettel_post, jt_express, etc');
            }
            
            // Phí vận chuyển thực tế (có thể khác phí ước tính)
            if (!in_array('actual_shipping_fee', $columns)) {
                $table->decimal('actual_shipping_fee', 12, 2)->nullable()->after('shipping_carrier');
            }
            
            // Thời gian dự kiến giao hàng
            if (!in_array('estimated_delivery_at', $columns)) {
                $table->timestamp('estimated_delivery_at')->nullable()->after('actual_shipping_fee');
            }
            
            // Ghi chú vận chuyển từ seller
            if (!in_array('shipping_note', $columns)) {
                $table->text('shipping_note')->nullable()->after('estimated_delivery_at');
            }
            
            // === XÁC NHẬN NHẬN HÀNG TỪ BUYER ===
            // Thời gian buyer xác nhận nhận hàng
            if (!in_array('buyer_confirmed_at', $columns)) {
                $table->timestamp('buyer_confirmed_at')->nullable()->after('delivered_at');
            }
            
            // Hình ảnh xác nhận nhận hàng (buyer chụp)
            if (!in_array('delivery_confirmation_images', $columns)) {
                $table->json('delivery_confirmation_images')->nullable()->after('buyer_confirmed_at')
                    ->comment('Hình ảnh xác nhận nhận hàng từ buyer');
            }
            
            // Ghi chú khi nhận hàng
            if (!in_array('delivery_confirmation_note', $columns)) {
                $table->text('delivery_confirmation_note')->nullable()->after('delivery_confirmation_images');
            }
            
            // Đánh giá tình trạng hàng khi nhận
            if (!in_array('delivery_condition', $columns)) {
                $table->enum('delivery_condition', ['good', 'damaged', 'missing_items', 'wrong_item'])->nullable()->after('delivery_confirmation_note')
                    ->comment('Tình trạng hàng: good=tốt, damaged=hư hỏng, missing_items=thiếu hàng, wrong_item=sai hàng');
            }
            
            // === LỊCH SỬ VẬN CHUYỂN ===
            // Lịch sử tracking (JSON array)
            if (!in_array('shipping_history', $columns)) {
                $table->json('shipping_history')->nullable()->after('delivery_condition')
                    ->comment('Lịch sử vận chuyển từ đơn vị vận chuyển');
            }
            
            // === THÔNG TIN SHIPPER ===
            if (!in_array('shipper_name', $columns)) {
                $table->string('shipper_name', 100)->nullable()->after('shipping_history');
            }
            if (!in_array('shipper_phone', $columns)) {
                $table->string('shipper_phone', 20)->nullable()->after('shipper_name');
            }
            
            // === HÌNH ẢNH GIAO HÀNG TỪ SHIPPER ===
            if (!in_array('delivery_proof_images', $columns)) {
                $table->json('delivery_proof_images')->nullable()->after('shipper_phone')
                    ->comment('Hình ảnh chứng minh đã giao hàng từ shipper');
            }
            
            // === THỜI GIAN TỰ ĐỘNG HOÀN THÀNH ===
            // Nếu buyer không xác nhận sau X ngày, tự động hoàn thành
            if (!in_array('auto_complete_at', $columns)) {
                $table->timestamp('auto_complete_at')->nullable()->after('delivery_proof_images')
                    ->comment('Thời gian tự động hoàn thành nếu buyer không xác nhận');
            }
        });
        
        // Thêm index cho các trường mới
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('shipping_carrier', 'idx_orders_shipping_carrier');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('buyer_confirmed_at', 'idx_orders_buyer_confirmed');
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('auto_complete_at', 'idx_orders_auto_complete');
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'shipping_carrier',
                'actual_shipping_fee',
                'estimated_delivery_at',
                'shipping_note',
                'buyer_confirmed_at',
                'delivery_confirmation_images',
                'delivery_confirmation_note',
                'delivery_condition',
                'shipping_history',
                'shipper_name',
                'shipper_phone',
                'delivery_proof_images',
                'auto_complete_at',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_shipping_carrier');
                $table->dropIndex('idx_orders_buyer_confirmed');
                $table->dropIndex('idx_orders_auto_complete');
            });
        } catch (\Exception $e) {}
    }
};
