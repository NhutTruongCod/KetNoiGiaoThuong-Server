<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';
    protected $description = 'Tự động hoàn thành đơn hàng đã giao nếu buyer không xác nhận sau thời gian quy định';

    public function handle()
    {
        $this->info('Đang kiểm tra đơn hàng cần tự động hoàn thành...');
        
        // Tìm các đơn hàng đã giao và quá hạn auto_complete_at
        $orders = Order::where('status', 'delivered')
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', now())
            ->whereNull('buyer_confirmed_at')
            ->get();
        
        $count = 0;
        
        foreach ($orders as $order) {
            try {
                $order->update([
                    'status' => 'completed',
                    'buyer_confirmed_at' => now(),
                    'delivery_confirmation_note' => 'Tự động hoàn thành do quá thời gian xác nhận',
                    'delivery_condition' => 'good',
                ]);
                
                // Thêm vào lịch sử vận chuyển
                $order->addShippingHistory(
                    'auto_completed',
                    'Đơn hàng tự động hoàn thành do quá thời gian xác nhận',
                    null
                );
                $order->save();
                
                // Thông báo cho buyer
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'title' => 'Đơn hàng đã tự động hoàn thành',
                    'message' => "Đơn hàng #{$order->order_number} đã tự động hoàn thành do quá thời gian xác nhận nhận hàng.",
                    'type' => 'order',
                    'data' => ['order_id' => $order->id],
                ]);
                
                // Thông báo cho seller
                Notification::create([
                    'user_id' => $order->seller_id,
                    'title' => 'Đơn hàng đã hoàn thành',
                    'message' => "Đơn hàng #{$order->order_number} đã tự động hoàn thành.",
                    'type' => 'order',
                    'data' => ['order_id' => $order->id],
                ]);
                
                $count++;
                $this->line("✓ Đơn hàng #{$order->order_number} đã tự động hoàn thành");
                
            } catch (\Exception $e) {
                Log::error("Lỗi tự động hoàn thành đơn hàng #{$order->order_number}: " . $e->getMessage());
                $this->error("✗ Lỗi đơn hàng #{$order->order_number}: " . $e->getMessage());
            }
        }
        
        $this->info("Hoàn thành! Đã tự động hoàn thành {$count} đơn hàng.");
        
        return Command::SUCCESS;
    }
}
