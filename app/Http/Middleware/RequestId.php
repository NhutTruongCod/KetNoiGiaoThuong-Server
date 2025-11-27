<?php
namespace App\Http\Middleware;
use Illuminate\Support\Str;
use Closure;
use Illuminate\Http\Request;

class RequestId
{
    public function handle(Request $request, Closure $next)
    {
        $rid = $request->header('X-Request-Id') ?: (string) Str::uuid();
        app()->instance('request_id', $rid);

        $response = $next($request);
        return $response->header('X-Request-Id', $rid);
    }
}
