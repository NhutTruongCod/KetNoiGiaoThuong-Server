<?php
namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class ReportOnOrderCreated implements ShouldQueue
{
    public function handle(OrderCreated $e): void
    {
        DB::table('transactions')->updateOrInsert(
            ['order_id' => $e->orderId],
            [
                'company_id'    => $e->userId,          // tùy mô hình: tạm ánh xạ userId = company_id
                'user_id'       => $e->userId,
                'amount_cents'  => $e->amountCents,
                'status'        => 'created',
                'request_id'    => $e->requestId,
                'correlation_id'=> $e->correlationId,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );
    }
}
