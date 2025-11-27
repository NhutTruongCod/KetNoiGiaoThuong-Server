<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Support\Correlation;

class RequestCorrelation
{
    public function handle($request, Closure $next)
    {
        $requestId     = $request->header('X-Request-Id') ?? (string) Str::uuid();
        $correlationId = $request->header('X-Correlation-Id') ?? $requestId;

        Correlation::set($requestId, $correlationId);
        Log::withContext([
            'request_id'     => $requestId,
            'correlation_id' => $correlationId,
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Correlation-Id', $correlationId);
        return $response;
    }
}
