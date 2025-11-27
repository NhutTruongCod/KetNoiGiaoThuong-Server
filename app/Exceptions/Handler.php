<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Nếu là request API (hoặc đã bị ForceJson ép), trả JSON đồng nhất
        $rid = app('request_id') ?? null;
        $meta = [
            'request_id' => $rid,
            'timestamp'  => now()->toIso8601String(),
        ];

        $map = function (int $status, string $code, string $message, $details = null) use ($meta) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => $code,
                    'message' => $message,
                    'details' => $details,
                ],
                'meta'    => $meta,
            ], $status);
        };

        if ($e instanceof ValidationException) {
            return $map(422, 'VALIDATION_ERROR', 'Dữ liệu không hợp lệ', $e->errors());
        }

        if ($e instanceof ModelNotFoundException) {
            return $map(404, 'NOT_FOUND', 'Không tìm thấy tài nguyên');
        }

        if ($e instanceof AuthenticationException) {
            return $map(401, 'UNAUTHENTICATED', 'Chưa xác thực');
        }

        if ($e instanceof AuthorizationException) {
            return $map(403, 'FORBIDDEN', 'Không có quyền');
        }

        if ($e instanceof HttpException) {
            return $map($e->getStatusCode(), 'HTTP_ERROR', $e->getMessage() ?: 'Lỗi HTTP');
        }

        if ($e instanceof QueryException) {
            return $map(500, 'DB_ERROR', 'Lỗi cơ sở dữ liệu');
        }

        // Dev: vẫn muốn thấy stacktrace
        if (config('app.debug')) {
            return parent::render($request, $e);
        }

        // Fallback production
        return $map(500, 'INTERNAL_ERROR', 'Có lỗi xảy ra, vui lòng thử lại sau');
    
    }
}
