<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Lấy danh sách thông báo của user hiện tại (có phân trang + filter)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user('api');

            $query = Notification::where('user_id', $user->id);

            if ($request->filled('is_read')) {
                $isRead = filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($isRead)) {
                    $query->where('is_read', $isRead);
                }
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            $perPage = (int) $request->get('per_page', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

            $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'total_pages' => $notifications->lastPage(),
                    'total_items' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notifications/{id}
     * Xem chi tiết một thông báo
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user('api');

            $notification = Notification::where('user_id', $user->id)->find($id);

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/notifications/{id}/read
     * Đánh dấu một thông báo là đã đọc
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user('api');

            $notification = Notification::where('user_id', $user->id)->find($id);

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found',
                ], 404);
            }

            if (!$notification->is_read) {
                $notification->is_read = true;
                $notification->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/notifications/read-all
     * Đánh dấu tất cả thông báo của user hiện tại là đã đọc
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user('api');

            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/notifications/{id}
     * Xóa một thông báo cụ thể
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user('api');

            $notification = Notification::where('user_id', $user->id)->find($id);

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found',
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/notifications/delete-all
     * Xóa tất cả thông báo của user hiện tại
     */
    public function destroyAll(Request $request)
    {
        try {
            $user = $request->user('api');

            Notification::where('user_id', $user->id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

