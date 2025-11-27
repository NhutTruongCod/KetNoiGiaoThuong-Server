<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Listeners\SendOrderNotifications;
use App\Listeners\UpdateReports;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            UpdateReports::class,        // cập nhật báo cáo/tồn kho nếu cần
            SendOrderNotifications::class,
        ],
        PaymentSucceeded::class => [
            UpdateReports::class,
            SendOrderNotifications::class,
        ],
        ShipmentCreated::class => [
            UpdateReports::class,
            SendOrderNotifications::class,
        ],
        \App\Events\OrderCreated::class => [
        \App\Listeners\ReportOnOrderCreated::class,
        ],
        // \App\Events\PaymentSucceeded::class => [
        //     \App\Listeners\ReportOnPaymentSucceeded::class,
        // ],
    ];
    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }

    public function boot(): void {}
    
}
