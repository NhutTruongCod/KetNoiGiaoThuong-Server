<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        'avatar', // Don't trim file uploads
    ];

    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        // Skip trimming for file upload requests
        if ($request->hasHeader('Content-Type') && 
            str_contains($request->header('Content-Type'), 'multipart/form-data')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
