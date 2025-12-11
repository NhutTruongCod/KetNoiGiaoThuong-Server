<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Shop;
use App\Models\Listing;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::where('role', 'buyer')->get();
        $sellers = User::where('role', 'seller')->get();
        $shops = Shop::all();
        $listings = Listing::all();

        if ($buyers->count() === 0) {
            $this->command->warn('⚠️  No buyers found. Skipping order seeder.');
            return;
        }

        if ($shops->count() === 0 || $listings->count() === 0) {
            $this->command->warn('⚠️  No shops or listings found. Skipping order seeder.');
            return;
        }

        // Tăng tỷ lệ completed orders để có nhiều reviews hơn
        $statuses = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'completed', 'completed', 'completed', 'cancelled'];
        $paymentMethods = ['cod', 'vnpay', 'momo', 'bank_transfer'];
        $count = 0;

        foreach ($buyers as $index => $buyer) {
            // Mỗi buyer tạo 1-3 orders
            $orderCount = rand(1, 3);

            for ($i = 0; $i < $orderCount; $i++) {
                $shop = $shops[($index + $i) % $shops->count()];
                $listing = $listings[($index + $i) % $listings->count()];
                $seller = $sellers->count() > 0 ? $sellers[($index + $i) % $sellers->count()] : null;
                
                $quantity = rand(1, 3);
                $unitPrice = $listing->price ?? rand(100000, 5000000);
                $totalAmount = $unitPrice * $quantity;
                $shippingFee = rand(0, 50000);
                $discountAmount = rand(0, 100000);
                $taxAmount = 0;
                $finalAmount = $totalAmount + $shippingFee + $taxAmount - $discountAmount;
                
                // Đảm bảo 3 orders đầu tiên là completed để có reviews
                if ($count < 3) {
                    $status = 'completed';
                } else {
                    $status = $statuses[array_rand($statuses)];
                }
                $paymentStatus = in_array($status, ['delivered', 'completed']) ? 'paid' : 'unpaid';
                
                $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                $orderData = [
                    'order_number' => $orderNumber,
                    'buyer_id' => $buyer->id,
                    'seller_id' => $seller ? $seller->id : ($shop->owner_user_id ?? null),
                    'shop_id' => $shop->id,
                    'listing_id' => $listing->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'final_amount' => $finalAmount,
                    'status' => $status,
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'payment_status' => $paymentStatus,
                    'shipping_address' => [
                        'name' => $buyer->name,
                        'phone' => $buyer->phone ?? '0987654321',
                        'address' => '456 Lê Lợi',
                        'ward' => 'Phường ' . rand(1, 10),
                        'district' => 'Quận ' . rand(1, 12),
                        'city' => 'TP.HCM',
                        'postal_code' => '700000'
                    ],
                    'note' => rand(0, 1) ? 'Giao giờ hành chính' : null,
                ];

                // Add tracking for shipped/delivered orders
                if (in_array($status, ['shipping', 'delivered', 'completed'])) {
                    $orderData['tracking_number'] = 'VN' . rand(100000000, 999999999);
                    $orderData['shipped_at'] = now()->subDays(rand(1, 5));
                }

                if (in_array($status, ['delivered', 'completed'])) {
                    $orderData['delivered_at'] = now()->subDays(rand(0, 2));
                }

                if ($status === 'cancelled') {
                    $orderData['cancelled_at'] = now()->subDays(rand(0, 3));
                    $orderData['cancel_reason'] = 'Khách hàng đổi ý';
                }

                Order::create($orderData);
                $count++;
            }
        }

        $this->command->info('✅ Created ' . $count . ' orders');
    }
}
