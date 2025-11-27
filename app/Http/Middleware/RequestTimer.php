<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestTimer
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (int) ((microtime(true) - $start) * 1000);
        $response->headers->set('X-Response-Time', "{$ms}ms");
        return $response;
    }
}
