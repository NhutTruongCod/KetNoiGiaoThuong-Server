<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/users",
     *   summary="Danh sách người dùng (có phân trang)",
     *   tags={"Users"},
     *   @OA\Parameter(
     *     name="per_page", in="query", description="Số item mỗi trang (1..100)",
     *     @OA\Schema(type="integer", minimum=1, maximum=100), example=20
     *   ),
     *   @OA\Parameter(
     *     name="page", in="query", description="Trang cần lấy",
     *     @OA\Schema(type="integer", minimum=1), example=1
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    // GET /api/users
    public function index(Request $request)
    {
        // có thể thêm filter/search ở đây nếu cần
        $q = User::query()->orderByDesc('id');

        $paginator = $q->paginate($request->perPage()); // clamp 1..100, mặc định 20
        return response()->page($paginator);            // chuẩn JSON: data + meta + links
    }
}
