<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Support\Correlation;

abstract class BaseEvent
{
    use Dispatchable, SerializesModels;

    public string $requestId;
    public string $correlationId;

    public function __construct()
    {
        // KHÔNG gọi parent::__construct() ở đây (class này không có cha)
        $this->requestId     = Correlation::requestId() ?? '';
        $this->correlationId = Correlation::correlationId() ?? '';
    }

    // Payload chung cho mọi event; con sẽ merge vào
    protected function basePayload(): array
    {
        return [
            'event'          => class_basename(static::class),
            'request_id'     => $this->requestId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
