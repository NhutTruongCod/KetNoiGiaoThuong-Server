<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use App\Models\Listing;
use App\Models\Shop;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            $notifications = [];
            
            // 1. Order notifications - chỉ tạo cho orders mà user là buyer hoặc seller
            $userOrders = Order::where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id)
                ->take(3)
                ->get();
            
            foreach ($userOrders as $order) {
                $isBuyer = $order->buyer_id == $user->id;
                $notifications[] = [
                    'type' => 'order',
                    'title' => $isBuyer ? 'Cập nhật đơn hàng' : 'Đơn hàng mới',
                    'message' => $isBuyer 
                        ? 'Đơn hàng #' . $order->order_number . ' đã được cập nhật'
                        : 'Bạn có đơn hàng mới #' . $order->order_number,
                    'icon' => 'shopping-cart',
                    'priority' => 'high',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->final_amount,
                        'status' => $order->status,
                    ],
                    'action_url' => '/orders/' . $order->id,
                    'action_text' => 'Xem đơn hàng',
                ];
            }
            
            // 2. Listing notifications - chỉ cho listings của user
            $userListings = Listing::where('user_id', $user->id)->take(2)->get();
            foreach ($userListings as $listing) {
                $notifications[] = [
                    'type' => 'listing',
                    'title' => 'Tin đăng được duyệt',
                    'message' => 'Tin đăng "' . $listing->title . '" đã được duyệt',
                    'icon' => 'check-circle',
                    'priority' => 'normal',
                    'data' => [
                        'listing_id' => $listing->id,
                        'title' => $listing->title,
                    ],
                    'action_url' => '/listings/' . $listing->id,
                    'action_text' => 'Xem tin đăng',
                ];
            }
            
            // 3. Shop notifications - chỉ cho shop của user
            $userShop = Shop::where('owner_user_id', $user->id)->first();
            if ($userShop) {
                $notifications[] = [
                    'type' => 'shop',
                    'title' => 'Gian hàng được xác minh',
                    'message' => 'Gian hàng "' . $userShop->name . '" đã được xác minh',
                    'icon' => 'store',
                    'priority' => 'high',
                    'data' => [
                        'shop_id' => $userShop->id,
                        'shop_name' => $userShop->name,
                    ],
                    'action_url' => '/shops/' . $userShop->id,
                    'action_text' => 'Xem gian hàng',
                ];
            }
            
            // 4. Promotion notifications - chỉ cho promotions của shop của user
            if ($userShop) {
                $userPromotions = Promotion::where('shop_id', $userShop->id)->take(2)->get();
                foreach ($userPromotions as $promotion) {
                    $notifications[] = [
                        'type' => 'promotion',
                        'title' => 'Quảng cáo được duyệt',
                        'message' => 'Chiến dịch quảng cáo của bạn đã được duyệt',
                        'icon' => 'megaphone',
                        'priority' => 'normal',
                        'data' => [
                            'promotion_id' => $promotion->id,
                            'listing_id' => $promotion->listing_id,
                        ],
                        'action_url' => '/promotion/' . $promotion->id,
                        'action_text' => 'Xem quảng cáo',
                    ];
                }
            }
            
            // 5. System notifications - cho tất cả users
            $notifications[] = [
                'type' => 'system',
                'title' => 'Cập nhật hệ thống',
                'message' => 'Hệ thống sẽ bảo trì vào 2h sáng ngày 05/12/2025',
                'icon' => 'info',
                'priority' => 'low',
                'data' => ['maintenance_date' => '2025-12-05T02:00:00.000000Z'],
                'action_url' => null,
                'action_text' => null,
            ];
            
            // 6. Wallet notification
            $notifications[] = [
                'type' => 'wallet',
                'title' => 'Nạp tiền thành công',
                'message' => 'Bạn đã nạp thành công 1,000,000 VND vào ví',
                'icon' => 'wallet',
                'priority' => 'high',
                'data' => ['amount' => 1000000],
                'action_url' => '/wallet',
                'action_text' => 'Xem ví',
            ];
            
            // Tạo notifications
            foreach ($notifications as $notif) {
                $isRead = rand(1, 10) > 4; // 60% đã đọc
                
                $data = [
                    'user_id' => $user->id,
                    'type' => $notif['type'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'data' => $notif['data'],
                    'action_url' => $notif['action_url'],
                    'action_text' => $notif['action_text'],
                    'icon' => $notif['icon'],
                    'priority' => $notif['priority'],
                    'is_read' => $isRead,
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];

                if ($isRead) {
                    $data['read_at'] = now()->subDays(rand(0, 15));
                }

                Notification::create($data);
            }
        }

        $this->command->info('Notifications seeded with correct user relationships!');
    }
}
