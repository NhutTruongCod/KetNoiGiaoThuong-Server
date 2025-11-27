<?php
namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Jobs\IncrementReportCounters;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateReports implements ShouldQueue
{
    public function handle($event): void
    {
        if ($event instanceof OrderCreated || $event instanceof PaymentSucceeded) {
            IncrementReportCounters::dispatch(
                orderId: $event->orderId,
                amount: $event->total
            );
        }
        // ShipmentCreated có thể không thay đổi doanh thu, nhưng bạn có thể log event ở bảng analytics (mục 4)
    }
}
