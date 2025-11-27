<?php

namespace App\Events;

class OrderCreated extends BaseEvent
{
    public function __construct(
        public string $orderId,
        public int $userId,
        public int $amountCents
    ) {
        parent::__construct(); // OK vì class này có cha là BaseEvent
    }

    public function toArray(): array
    {
        return array_merge($this->basePayload(), [
            'order_id'     => $this->orderId,
            'user_id'      => $this->userId,
            'amount_cents' => $this->amountCents,
        ]);
    }
}
