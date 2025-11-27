<?php

namespace App\Http\Controllers;

use App\Models\DataExportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataExportController extends Controller
{
    /**
     * POST /api/data/export/request
     * Tạo yêu cầu trích xuất dữ liệu cá nhân
     */
    public function requestExport(Request $request)
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:csv,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Không cho user tạo quá nhiều request đang chờ
        $pendingExists = DataExportRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is already a pending export request',
            ], 409);
        }

        $format = $request->input('format', 'json');

        // Ở đây để đơn giản ta xử lý đồng bộ: tạo request và đánh dấu completed luôn.
        $export = DataExportRequest::create([
            'user_id' => $user->id,
            'format' => $format,
            'status' => 'completed',
            'download_url' => null, // có thể gán URL nếu sau này có file thật
            'requested_at' => now(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data export request created',
            'data' => [
                'request_id' => $export->id,
                'status' => $export->status,
                'format' => $export->format,
            ],
        ], 201);
    }

    /**
     * GET /api/data/export/status/{id}
     * Xem trạng thái một yêu cầu
     */
    public function status(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export request not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $export->id,
                'format' => $export->format,
                'status' => $export->status,
                'requested_at' => $export->requested_at,
                'completed_at' => $export->completed_at,
                'download_url' => $export->download_url,
            ],
        ]);
    }

    /**
     * GET /api/data/export/download/{id}
     * Trả thông tin tải xuống cho một yêu cầu đã hoàn tất
     */
    public function download(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export request not found',
            ], 404);
        }

        if ($export->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Export is not completed yet',
            ], 409);
        }

        // Hiện tại chỉ trả URL (nếu có); khi có file thực tế có thể redirect hoặc stream file
        return response()->json([
            'status' => 'success',
            'data' => [
                'download_url' => $export->download_url,
                'format' => $export->format,
            ],
        ]);
    }

    /**
     * DELETE /api/data/export/cancel/{id}
     * Hủy một yêu cầu đang xử lý
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export request not found',
            ], 404);
        }

        if (!in_array($export->status, ['pending', 'processing'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending or processing requests can be cancelled',
            ], 409);
        }

        $export->status = 'failed';
        $export->completed_at = now();
        $export->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Export request cancelled',
        ]);
    }

    /**
     * GET /api/data/export/history
     * Xem lịch sử yêu cầu xuất dữ liệu của user
     */
    public function history(Request $request)
    {
        $user = $request->user('api');

        $requests = DataExportRequest::where('user_id', $user->id)
            ->orderBy('requested_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'total_pages' => $requests->lastPage(),
                'total_items' => $requests->total(),
                'per_page' => $requests->perPage(),
            ],
        ]);
    }
}

