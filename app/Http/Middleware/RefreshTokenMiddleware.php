<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Middleware tự động refresh token khi gần hết hạn
 * 
 * Nếu token còn dưới 30 phút, sẽ tự động tạo token mới
 * và trả về trong header X-New-Token
 */
class RefreshTokenMiddleware
{
    /**
     * Thời gian còn lại (phút) để trigger refresh
     */
    protected $refreshThreshold = 30;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            // Kiểm tra nếu user đã authenticated
            if (!auth('api')->check()) {
                return $response;
            }

            // Lấy payload của token hiện tại
            $payload = JWTAuth::parseToken()->getPayload();
            $exp = $payload->get('exp');
            $now = time();
            
            // Tính thời gian còn lại (phút)
            $minutesRemaining = ($exp - $now) / 60;

            // Nếu còn dưới threshold, tạo token mới
            if ($minutesRemaining > 0 && $minutesRemaining < $this->refreshThreshold) {
                $newToken = auth('api')->refresh();
                
                // Thêm token mới vào response header
                $response->headers->set('X-New-Token', $newToken);
                $response->headers->set('X-Token-Expires-In', config('jwt.ttl') * 60);
                
                // Cho phép FE đọc header này
                $response->headers->set('Access-Control-Expose-Headers', 'X-New-Token, X-Token-Expires-In');
            }
        } catch (TokenExpiredException $e) {
            // Token đã hết hạn, không làm gì
        } catch (JWTException $e) {
            // Lỗi JWT khác, không làm gì
        }

        return $response;
    }
}
