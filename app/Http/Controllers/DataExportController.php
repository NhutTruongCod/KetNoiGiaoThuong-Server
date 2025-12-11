<?php

namespace App\Http\Controllers;

use App\Models\DataExportRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DataExportController extends Controller
{
    /**
     * 1. POST /data/export/request - Tạo yêu cầu xuất dữ liệu
     */
    public function requestExport(Request $request)
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'data_types' => 'required|array',
            'data_types.*' => 'in:profile,listings,orders,reviews,messages,payments,notifications,login_history,all',
            'format' => 'required|in:csv,json,xlsx',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'include_deleted' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for existing pending/processing request
        $existingRequest = DataExportRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'You already have a pending export request. Please wait for it to complete.'
            ], 400);
        }

        $dataTypes = $request->data_types;
        
        // If 'all' is selected, include all types
        if (in_array('all', $dataTypes)) {
            $dataTypes = ['profile', 'listings', 'orders', 'reviews', 'messages', 'payments', 'notifications', 'login_history'];
        }

        // Estimate completion time (1 minute per data type)
        $estimatedMinutes = count($dataTypes) * 1;
        $estimatedCompletion = now()->addMinutes($estimatedMinutes);

        // Create export request
        $export = DataExportRequest::create([
            'user_id' => $user->id,
            'format' => $request->format,
            'data_types' => $dataTypes,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'include_deleted' => $request->include_deleted ?? false,
            'status' => 'pending',
            'progress' => 0,
            'requested_at' => now(),
            'estimated_completion' => $estimatedCompletion,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Yêu cầu xuất dữ liệu',
            'message' => 'Yêu cầu xuất dữ liệu của bạn đang được xử lý.',
            'data' => ['export_id' => $export->id],
            'icon' => 'download',
            'priority' => 'normal',
        ]);

        // TODO: Dispatch background job to process export
        // ProcessDataExport::dispatch($export);

        return response()->json([
            'message' => 'Data export request created successfully. We will process your request and notify you when it\'s ready.',
            'data' => [
                'id' => $export->id,
                'user_id' => $export->user_id,
                'data_types' => $export->data_types,
                'format' => $export->format,
                'status' => $export->status,
                'date_from' => $export->date_from,
                'date_to' => $export->date_to,
                'estimated_completion' => $export->estimated_completion,
                'created_at' => $export->created_at,
            ]
        ], 201);
    }

    /**
     * 2. GET /data/export/status/{id} - Kiểm tra trạng thái
     */
    public function status(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'message' => 'Export request not found'
            ], 404);
        }

        $response = [
            'id' => $export->id,
            'user_id' => $export->user_id,
            'data_types' => $export->data_types,
            'format' => $export->format,
            'status' => $export->status,
            'progress' => $export->progress,
            'current_step' => $export->current_step,
            'estimated_completion' => $export->estimated_completion,
            'created_at' => $export->created_at,
            'updated_at' => $export->updated_at,
        ];

        if ($export->status === 'completed') {
            $response['file_size'] = $export->file_size;
            $response['file_size_human'] = $export->file_size_human;
            $response['download_url'] = $export->download_url;
            $response['expires_at'] = $export->expires_at;
            $response['completed_at'] = $export->completed_at;
        }

        return response()->json($response);
    }

    /**
     * 3. GET /data/export/download/{id} - Download file
     */
    public function download(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'message' => 'Export request not found'
            ], 404);
        }

        if ($export->status !== 'completed') {
            return response()->json([
                'message' => 'Export is not completed yet'
            ], 400);
        }

        if ($export->isExpired()) {
            $export->markAsExpired();
            return response()->json([
                'message' => 'Export file has expired. Please create a new export request.'
            ], 410);
        }

        if ($export->downloads_count >= $export->max_downloads) {
            return response()->json([
                'message' => 'Maximum download limit reached'
            ], 429);
        }

        // Increment download count
        $export->incrementDownloads();

        return response()->json([
            'id' => $export->id,
            'download_url' => $export->download_url,
            'file_name' => $export->file_name,
            'file_size' => $export->file_size,
            'file_size_human' => $export->file_size_human,
            'format' => $export->format,
            'expires_at' => $export->expires_at,
            'downloads_count' => $export->downloads_count,
            'max_downloads' => $export->max_downloads,
        ]);
    }

    /**
     * 4. DELETE /data/export/cancel/{id} - Hủy yêu cầu
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user('api');

        $export = DataExportRequest::where('user_id', $user->id)->find($id);

        if (!$export) {
            return response()->json([
                'message' => 'Export request not found'
            ], 404);
        }

        if (!in_array($export->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Cannot cancel completed or expired export request'
            ], 400);
        }

        $export->cancel();

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Yêu cầu xuất dữ liệu đã hủy',
            'message' => 'Yêu cầu xuất dữ liệu của bạn đã được hủy.',
            'data' => ['export_id' => $export->id],
            'icon' => 'x-circle',
            'priority' => 'normal',
        ]);

        return response()->json([
            'message' => 'Export request cancelled successfully',
            'data' => [
                'id' => $export->id,
                'status' => $export->status,
                'cancelled_at' => $export->cancelled_at,
            ]
        ]);
    }

    /**
     * 5. GET /data/export/history - Lịch sử
     */
    public function history(Request $request)
    {
        $user = $request->user('api');

        $query = DataExportRequest::where('user_id', $user->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $exports = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $exports->items();
        
        // Add file_size_human to each item
        foreach ($data as $item) {
            $item->file_size_human = $item->file_size_human;
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $exports->currentPage(),
                'per_page' => $exports->perPage(),
                'total' => $exports->total(),
                'last_page' => $exports->lastPage(),
            ],
        ]);
    }

    /**
     * Helper: Generate mock export file (for testing)
     * In production, this would be a background job
     */
    private function generateExportFile($export)
    {
        // Mock file generation
        $fileName = "user-{$export->user_id}-export-" . now()->format('Ymd') . ".zip";
        $fileSize = rand(1000000, 5000000); // 1-5 MB
        $downloadUrl = url("/storage/exports/{$fileName}");

        $export->markAsCompleted($fileName, $fileSize, $downloadUrl);

        // Create notification
        Notification::create([
            'user_id' => $export->user_id,
            'type' => 'system',
            'title' => 'Xuất dữ liệu hoàn tất',
            'message' => 'Dữ liệu của bạn đã sẵn sàng để tải xuống.',
            'data' => ['export_id' => $export->id],
            'action_url' => "/data/export/download/{$export->id}",
            'action_text' => 'Tải xuống',
            'icon' => 'download',
            'priority' => 'high',
        ]);
    }
}
