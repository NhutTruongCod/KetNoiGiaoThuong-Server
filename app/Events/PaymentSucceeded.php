<?php

namespace App\Events;

class PaymentSucceeded extends BaseEvent
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public int $amountCents,
        public string $provider
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->basePayload(), [
            'payment_id'   => $this->paymentId,
            'order_id'     => $this->orderId,
            'amount_cents' => $this->amountCents,
            'provider'     => $this->provider,
        ]);
    }
}
