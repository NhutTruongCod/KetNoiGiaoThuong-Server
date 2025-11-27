<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyScope
{
    /**
     * Ràng buộc: user chỉ được xem/báo cáo trong phạm vi company_id của chính mình.
     * - company_id lấy từ query (?company_id=) hoặc body (JSON/form).
     * - Nếu user chưa có company_id hoặc request không truyền company_id => cho qua.
     * - Nếu có cả hai mà khác nhau => 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cidReq  = (int) ($request->query('company_id') ?? $request->input('company_id'));
        $cidUser = (int) optional($request->user())->company_id;

        if ($cidUser && $cidReq && $cidReq !== $cidUser) {
            return response()->json([
                'message'     => 'Forbidden company scope',
                'company_id'  => $cidReq,
                'your_company_id' => $cidUser,
            ], 403);
        }

        return $next($request);
    }
}
