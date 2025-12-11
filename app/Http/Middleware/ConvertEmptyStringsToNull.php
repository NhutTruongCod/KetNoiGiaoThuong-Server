<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull as Middleware;

class ConvertEmptyStringsToNull extends Middleware
{
    /**
     * The names of the attributes that should not be converted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'avatar', // Don't convert file uploads
    ];

    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        // Skip conversion for file upload requests
        if ($request->hasHeader('Content-Type') && 
            str_contains($request->header('Content-Type'), 'multipart/form-data')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
