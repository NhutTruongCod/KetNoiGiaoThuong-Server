<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicEnvAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Đọc từ config (đã lấy từ .env)
        $user = config('basic_auth.user');
        $pass = config('basic_auth.pass');

        // Nếu CHƯA cấu hình user/pass => chặn luôn (tránh bị bỏ qua)
        if (!is_string($user) || $user === '' || !is_string($pass) || $pass === '') {
            return response('BASIC_AUTH_USER/PASS not set', 500);
        }

        $auth = $request->header('Authorization');
        if ($auth && preg_match('/Basic\s+(.*)$/i', $auth, $m)) {
            $decoded = base64_decode($m[1]);
            [$u, $p] = array_pad(explode(':', $decoded, 2), 2, '');

            if (hash_equals($user, $u) && hash_equals($pass, $p)) {
                return $next($request);
            }
        }

        return response('Unauthorized', 401)->header('WWW-Authenticate', 'Basic realm="TradeHub API"');
    }
}
