<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserProfileController extends Controller
{
    /**
     * POST /api/user/avatar/base64
     * Upload avatar từ base64 string (dễ dàng hơn cho frontend)
     */
    public function uploadAvatarBase64(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'avatar_base64' => 'required|string',
                'filename' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Decode base64
            $base64String = $request->avatar_base64;
            
            // Remove data:image/...;base64, prefix if exists
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $extension = $matches[1];
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
            } else {
                $extension = 'jpg'; // default
            }

            $imageData = base64_decode($base64String);

            if ($imageData === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid base64 string'
                ], 400);
            }

            // Validate image
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedMimes)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid image type. Only JPEG, PNG, GIF allowed.'
                ], 400);
            }

            // Check size (max 2MB)
            if (strlen($imageData) > 2 * 1024 * 1024) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Image too large. Max 2MB.'
                ], 400);
            }

            // Xóa avatar cũ
            if ($user->avatar_url) {
                $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Save new avatar
            $filename = $user->id . '_' . time() . '.' . $extension;
            $path = 'avatars/' . $filename;
            
            Storage::disk('public')->put($path, $imageData);

            $url = url('storage/' . $path);

            // Update database
            $user->avatar_url = $url;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => $url,
                    'filename' => $filename,
                    'size' => strlen($imageData),
                    'mime_type' => $mimeType
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/user/avatar
     * Upload avatar và trả về URL
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $user = auth('api')->user();

            // Debug log
            \Log::info('Upload Avatar Request', [
                'has_file' => $request->hasFile('avatar'),
                'files' => $request->allFiles(),
                'all_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            // Check if file exists
            if (!$request->hasFile('avatar')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No file uploaded',
                    'debug' => [
                        'files' => $request->allFiles(),
                        'content_type' => $request->header('Content-Type')
                    ]
                ], 400);
            }

            // Validate file upload
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048', // Max 2MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid file',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Xóa avatar cũ nếu có
            if ($user->avatar_url) {
                $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Upload file mới
            $file = $request->file('avatar');
            $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');

            // Tạo URL đầy đủ
            $url = url('storage/' . $path);

            // Cập nhật avatar_url trong database
            $user->avatar_url = $url;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => $url,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/user/profile
     * Lấy thông tin user profile (bao gồm avatar và subscription)
     */
    public function getProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            $user->load('subscriptionPlan');

            // Subscription info
            $subscriptionInfo = null;
            if ($user->subscription_plan_id && $user->subscription_expires_at) {
                $isActive = $user->subscription_expires_at->isFuture();
                $subscriptionInfo = [
                    'plan_id' => $user->subscription_plan_id,
                    'plan_name' => $user->subscriptionPlan?->name,
                    'badge' => $user->subscriptionPlan?->badge,
                    'commission_rate' => $user->getCommissionRate(),
                    'search_boost' => $user->getSearchBoost(),
                    'expires_at' => $user->subscription_expires_at,
                    'is_active' => $isActive,
                    'days_remaining' => $isActive ? now()->diffInDays($user->subscription_expires_at) : 0,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                    'is_verified' => $user->is_verified,
                    'is_active' => $user->is_active,
                    'subscription' => $subscriptionInfo,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/user/profile
     * Cập nhật thông tin user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:191',
                'phone' => 'sometimes|string|max:32',
                'avatar_url' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Cập nhật các trường được phép
            if ($request->has('full_name')) {
                $user->full_name = $request->full_name;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->has('avatar_url')) {
                $user->avatar_url = $request->avatar_url;
            }

            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/user/avatar
     * Xóa avatar
     */
    public function deleteAvatar(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user->avatar_url) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No avatar to delete'
                ], 404);
            }

            // Xóa file từ storage
            $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Xóa URL trong database
            $user->avatar_url = null;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Avatar deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
