<?php
// app/Listeners/SendOrderNotifications.php
namespace App\Listeners;

// <<<--- THÊM DÒNG NÀY
use App\Events\OrderCreated;
use App\Events\Orders\OrderCreated as OrdersOrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Models\User; // Đảm bảo bạn đã import User model
use App\Notifications\OrderStatusChanged;
use App\Notifications\ShipmentCreatedNotification;
use App\Services\Notification\NotificationBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log; // Đảm bảo đã import Log
use OrderCreated as GlobalOrderCreated;

class SendOrderNotifications implements ShouldQueue
{
    public function __construct(private NotificationBridge $bridge) {}

    public function handle($event): void
    {
        // <<<--- BỎ DÒNG NÀY VÀ KHÔNG CẦN TRUYỀN $context NỮA
        // $context = $event->getTraceContext();

        // Log::info('SendOrderNotifications Listener Started', $context);
        Log::info('SendOrderNotifications Listener Started'); // Context tự động thêm vào

        try {
            switch (true) {
                case $event instanceof PaymentSucceeded:
                    // <<<--- Bỏ $context
                    $this->handlePaymentSucceeded($event);
                    break;

                case $event instanceof ShipmentCreated:
                    // <<<--- Bỏ $context
                    $this->handleShipmentCreated($event);
                    break;

                case $event instanceof OrdersOrderCreated:
                     // <<<--- Bỏ $context
                    $this->handleOrderCreated($event);
                    break;
            }

            // <<<--- Bỏ $context
            Log::info('SendOrderNotifications Listener Completed');

        } catch (\Exception $e) {
             // Log::error tự động lấy context hiện tại
            Log::error('SendOrderNotifications Listener Failed', [
                'error' => $e->getMessage(),
                // Không cần getTraceAsString() nếu bạn log cả exception
                // 'trace' => $e->getTraceAsString(),
                'exception' => $e, // Log cả object exception để xem chi tiết hơn
            ]);
            throw $e;
        }
    }

    // <<<--- Bỏ $context khỏi tham số
    private function handlePaymentSucceeded($event): void
    {
        // TODO: Get buyer from order
        // $buyer = User::find(...);
        // $this->bridge->to($buyer, new OrderStatusChanged(...));

        // <<<--- Bỏ $context
        Log::info('Payment notification sent');
    }

    // <<<--- Bỏ $context khỏi tham số
    private function handleShipmentCreated($event): void
    {
        // TODO: Get buyer from order
        // $buyer = User::find(...);
        // $this->bridge->to($buyer, new ShipmentCreatedNotification(...));

        // <<<--- Bỏ $context
        Log::info('Shipment notification sent');
    }

     // <<<--- Bỏ $context khỏi tham số
    private function handleOrderCreated($event): void
    {
        // TODO: Send order created notification
        // <<<--- Bỏ $context
        Log::info('Order created notification sent');
    }
}