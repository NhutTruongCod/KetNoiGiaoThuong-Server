<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCompleted
{
    use Dispatchable, SerializesModels;

    public int $orderId;
    public int $buyerId;
    public int $sellerId;
    public float $total;

    public function __construct(int $orderId, int $buyerId, int $sellerId, float $total)
    {
        $this->orderId = $orderId;
        $this->buyerId = $buyerId;
        $this->sellerId = $sellerId;
        $this->total   = $total;
    }
}
