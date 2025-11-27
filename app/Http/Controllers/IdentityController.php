<?php

namespace App\Http\Controllers;

use App\Models\UserIdentity;
use App\Models\IdentityVerificationRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IdentityController extends Controller
{
    /**
     * GET /api/identity/profile
     * Lấy thông tin hồ sơ cá nhân/doanh nghiệp
     */
    public function getProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            $identity = UserIdentity::where('user_id', $user->id)->first();

            if (!$identity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identity profile not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $identity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/identity/profile
     * Cập nhật hồ sơ cá nhân hoặc doanh nghiệp
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'identity_type' => 'sometimes|in:personal,business',
                'full_name' => 'sometimes|string|max:191',
                'date_of_birth' => 'nullable|date',
                'business_name' => 'nullable|string|max:191',
                'business_license' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:32',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            $identity = UserIdentity::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'identity_type',
                    'full_name',
                    'date_of_birth',
                    'business_name',
                    'business_license',
                    'address',
                    'phone'
                ])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Identity profile updated successfully',
                'data' => $identity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/identity/verify-request
     * Gửi hồ sơ xác minh doanh nghiệp (upload tài liệu)
     */
    public function submitVerifyRequest(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'document_type' => 'required|in:id_card,business_license,tax_code',
                'document_url' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing required information',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Kiểm tra xem có yêu cầu đang pending hoặc đã verified chưa
            $identity = UserIdentity::where('user_id', $user->id)->first();
            if ($identity && $identity->identity_status === 'verified') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business already verified'
                ], 409);
            }

            $pendingRequest = IdentityVerificationRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($pendingRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A verification request is already pending approval'
                ], 409);
            }

            // Tạo yêu cầu xác minh
            $verifyRequest = IdentityVerificationRequest::create([
                'user_id' => $user->id,
                'document_type' => $request->document_type,
                'document_url' => $request->document_url,
                'status' => 'pending',
            ]);

            // Cập nhật hoặc tạo identity_status sang 'pending'
            UserIdentity::updateOrCreate(
                ['user_id' => $user->id],
                ['identity_status' => 'pending']
            );

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Yêu cầu xác minh danh tính đã được gửi',
                'message' => 'Yêu cầu xác minh danh tính của bạn đang được xử lý. Vui lòng chờ admin xem xét.',
                'type' => 'system',
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification request submitted successfully',
                'data' => $verifyRequest
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/identity/verify-request/{id}/approve
     * Quản trị viên xử lý xác minh (duyệt)
     */
    public function approveVerifyRequest(Request $request, $id)
    {
        try {
            $admin = auth('api')->user();

            $verifyRequest = IdentityVerificationRequest::find($id);
            if (!$verifyRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification request not found'
                ], 404);
            }

            // Validate admin_note nếu có
            $validator = Validator::make($request->all(), [
                'admin_note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Duyệt yêu cầu
            $verifyRequest->status = 'approved';
            $verifyRequest->approved_by = $admin->id;
            $verifyRequest->admin_note = $request->input('admin_note');
            $verifyRequest->save();

            // Cập nhật identity_status sang 'verified'
            $identity = UserIdentity::where('user_id', $verifyRequest->user_id)->first();
            if ($identity) {
                $identity->identity_status = 'verified';
                $identity->verified_at = now();
                $identity->save();
            }

            Notification::create([
                'user_id' => $verifyRequest->user_id,
                'title' => 'Yêu cầu xác minh danh tính đã được duyệt',
                'message' => 'Hồ sơ danh tính của bạn đã được admin phê duyệt.',
                'type' => 'system',
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification request approved successfully',
                'data' => $verifyRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/identity/verify-request/{id}/reject
     * Quản trị viên từ chối xác minh
     */
    public function rejectVerifyRequest(Request $request, $id)
    {
        try {
            $admin = auth('api')->user();

            $verifyRequest = IdentityVerificationRequest::find($id);
            if (!$verifyRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification request not found'
                ], 404);
            }

            // Validate admin_note (bắt buộc khi reject)
            $validator = Validator::make($request->all(), [
                'admin_note' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin note is required when rejecting',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Từ chối yêu cầu
            $verifyRequest->status = 'rejected';
            $verifyRequest->approved_by = $admin->id;
            $verifyRequest->admin_note = $request->input('admin_note');
            $verifyRequest->save();

            // Cập nhật identity_status sang 'rejected'
            $identity = UserIdentity::where('user_id', $verifyRequest->user_id)->first();
            if ($identity) {
                $identity->identity_status = 'rejected';
                $identity->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Verification request rejected',
                'data' => $verifyRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/identity/verify-history
     * Lấy danh sách yêu cầu xác minh đã gửi
     */
    public function getVerifyHistory(Request $request)
    {
        try {
            $user = auth('api')->user();

            $history = IdentityVerificationRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $history,
                'total' => $history->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
