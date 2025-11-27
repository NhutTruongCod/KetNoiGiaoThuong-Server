<?php

namespace App\Support;

class Correlation
{
    protected static ?string $requestId = null;
    protected static ?string $correlationId = null;

    public static function set(string $requestId, string $correlationId): void
    {
        self::$requestId = $requestId;
        self::$correlationId = $correlationId;
    }

    public static function requestId(): ?string { return self::$requestId; }
    public static function correlationId(): ?string { return self::$correlationId; }
}
