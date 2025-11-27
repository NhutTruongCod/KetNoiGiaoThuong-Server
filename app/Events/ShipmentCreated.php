<?php

namespace App\Events;

class ShipmentCreated extends BaseEvent
{
    public function __construct(
        public string $shipmentId,
        public string $orderId,
        public string $carrier
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return array_merge($this->basePayload(), [
            'shipment_id' => $this->shipmentId,
            'order_id'    => $this->orderId,
            'carrier'     => $this->carrier,
        ]);
    }
}
