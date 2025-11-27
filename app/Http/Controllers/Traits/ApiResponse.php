<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function ok($data = [], array $meta = [])
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => $this->meta($meta),
        ]);
    }

    protected function created($data = [], array $meta = [])
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => $this->meta($meta),
        ], 201);
    }

    /**
     * Chuẩn hoá trả về phân trang.
     *
     * @param  LengthAwarePaginator  $paginator
     * @param  callable|null         $transform  Hàm map item -> item (ví dụ: fn($u) => [...])
     * @param  array                 $meta       Meta bổ sung
     */
    protected function paginated(LengthAwarePaginator $paginator, $transform = null, array $meta = [])
    {
        // Dùng items() + collect() để không gọi method ngoài interface
        $items = $transform
            ? collect($paginator->items())->map($transform)->values()->all()
            : $paginator->items();

        $pagination = [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => $this->meta(array_merge($meta, ['pagination' => $pagination])),
        ]);
    }

    private function meta(array $extra = []): array
    {
        return array_merge([
            'request_id' => app('request_id') ?? null,
            'timestamp'  => now()->toIso8601String(),
        ], $extra);
    }
}
