<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 1. GET /notifications - Lấy danh sách thông báo
     */
    public function index(Request $request)
    {
        $user = $request->user('api');

        $query = Notification::where('user_id', $user->id);

        // Filters
        if ($request->has('unread_only') && filter_var($request->unread_only, FILTER_VALIDATE_BOOLEAN)) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->has('date_from') || $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Summary
        $summary = [
            'total_notifications' => Notification::where('user_id', $user->id)->count(),
            'unread_count' => Notification::where('user_id', $user->id)->unread()->count(),
        ];

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * 2. GET /notifications/{id} - Xem chi tiết thông báo
     */
    public function show(Request $request, $id)
    {
        $user = $request->user('api');

        $notification = Notification::where('user_id', $user->id)->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        // Check ownership
        if ($notification->user_id != $user->id) {
            return response()->json([
                'message' => 'You can only view your own notifications'
            ], 403);
        }

        return response()->json($notification);
    }

    /**
     * 3. PUT /notifications/{id}/read - Đánh dấu đã đọc
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user('api');

        $notification = Notification::where('user_id', $user->id)->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => [
                'id' => $notification->id,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at,
            ]
        ]);
    }

    /**
     * 4. PUT /notifications/read-all - Đánh dấu tất cả đã đọc
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user('api');

        $readAt = now();
        $markedCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => $readAt,
            ]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'data' => [
                'marked_count' => $markedCount,
                'read_at' => $readAt,
            ]
        ]);
    }

    /**
     * 5. DELETE /notifications/{id} - Xóa một thông báo
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user('api');

        $notification = Notification::where('user_id', $user->id)->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        // Check ownership
        if ($notification->user_id != $user->id) {
            return response()->json([
                'message' => 'You can only delete your own notifications'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * 6. DELETE /notifications/delete-all - Xóa tất cả thông báo
     */
    public function destroyAll(Request $request)
    {
        $user = $request->user('api');

        $deletedCount = Notification::where('user_id', $user->id)->count();
        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'All notifications deleted successfully',
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }
}

