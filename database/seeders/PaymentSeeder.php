<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Order;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::with('listing.shop')->get();
        
        if ($orders->isEmpty()) {
            $this->command->warn('⚠️  No orders found. Please run OrderSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'completed', 'failed'];
        $methods = ['cod', 'vnpay', 'momo', 'bank_transfer'];
        
        foreach ($orders as $order) {
            if (!$order->listing || !$order->listing->shop) {
                continue;
            }
            
            $paymentStatus = $statuses[array_rand($statuses)];
            $method = $methods[array_rand($methods)];
            
            Payment::create([
                'user_id' => $order->buyer_id,
                'order_id' => $order->id,
                'payable_type' => 'App\\Models\\Order',
                'payable_id' => $order->id,
                'payment_type' => 'order',
                'payer_id' => $order->buyer_id,
                'payee_id' => $order->listing->shop->user_id,
                'method' => $method,
                'payment_gateway' => in_array($method, ['vnpay', 'momo', 'zalopay']) ? $method : null,
                'amount' => $order->total_amount,
                'currency' => 'VND',
                'status' => $paymentStatus,
                'description' => "Thanh toán đơn hàng #{$order->order_number}",
                'transaction_id' => strtoupper($method) . '-' . time() . '-' . $order->id,
                'paid_at' => $paymentStatus === 'completed' ? now() : null,
                'created_at' => $order->created_at,
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info('✅ Created ' . $orders->count() . ' payments');
    }
}
