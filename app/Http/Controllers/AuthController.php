<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserToken;
use App\Models\OtpCode;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use App\Mail\ResetPasswordMail;
use App\Mail\PasswordResetOtpMail;


class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới + gửi OTP email
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'full_name' => 'required|string|max:191',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400); // 400: Thiếu hoặc sai định dạng dữ liệu
        }

        // Kiểm tra email đã tồn tại (409 Conflict)
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email already exists',
            ], 409); // 409: Email đã được sử dụng
        }

        try {
            // Tạo user mới
            $user = User::create([
                'email' => $request->email,
                'full_name' => $request->full_name,
                'password_hash' => Hash::make($request->password),
                'provider' => 'local',
                'status' => 'active',
                'is_verified' => false,
                'is_active' => true,
            ]);

            $otp = random_int(100000, 999999);

            // Lưu OTP vào bảng otp_codes
            OtpCode::create([
                'user_id' => $user->id,
                'otp_code' => (string) $otp,
                'type' => 'email_verification',
                'expire_at' => now()->addMinutes(10),
                'is_used' => false,
                'created_at' => now(),
            ]);

            // Gửi mail xác minh
            Mail::to($user->email)->send(new VerifyEmailMail($otp, $user->full_name));

            return response()->json([
                'status' => 'success',
                'message' => 'Account created successfully. Please verify your email via OTP.',
                'data' => [
                    'email' => $user->email,
                    'expires_in_minutes' => 10,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi server rồi: ' . $e->getMessage(), // Hiện thông báo lỗi cụ thể
                'file' => $e->getFile(), // Lỗi ở file nào
                'line' => $e->getLine()  // Lỗi ở dòng bao nhiêu
            ], 500); // 500: Lỗi máy chủ nội bộ
        }
    }

    /**
     * Xác minh email qua OTP
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // Tìm OTP khớp với user và mã OTP
        $otpRecord = OtpCode::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('otp_code', $request->otp_code)
            ->first();

        // Kiểm tra OTP có tồn tại không
        if (!$otpRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP code'
            ], 401);
        }

        // Kiểm tra OTP đã được sử dụng chưa
        if ($otpRecord->is_used) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has already been used'
            ], 401);
        }

        // Kiểm tra OTP đã hết hạn chưa
        if ($otpRecord->expire_at <= now()) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired'
            ], 401);
        }

        $user->is_verified = true;
        $user->status = 'active';
        $user->save();

        // Đánh dấu đã dùng
        $otpRecord->is_used = true;
        $otpRecord->used_at = now();
        $otpRecord->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully'
        ]);
    }

    /**
     * Gửi lại OTP xác thực email
     */
    public function resendVerificationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email already verified'
            ], 400);
        }

        // Vô hiệu hóa các OTP cũ chưa dùng
        OtpCode::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]);

        // Tạo OTP mới
        $otp = random_int(100000, 999999);
        OtpCode::create([
            'user_id' => $user->id,
            'otp_code' => (string) $otp,
            'type' => 'email_verification',
            'expire_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($otp, $user->full_name));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send OTP email'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Verification OTP resent successfully',
            'data' => [
                'email' => $user->email,
                'expires_in_minutes' => 10
            ]
        ]);
    }

    /**
     * Đăng nhập & cấp JWT + refresh token
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        if (!$token = auth('api')->attempt($credentials)) {
            // Log failed login attempt if user exists
            $user = User::where('email', $request->email)->first();
            if ($user) {
                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'success' => false,
                    'logged_in_at' => now(),
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Invalid email or password'], 401);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if (!$user->is_verified) {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'success' => false,
                'logged_in_at' => now(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Please verify your email first'], 403);
        }

        if ($user->status !== 'active' || !$user->is_active) {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'success' => false,
                'logged_in_at' => now(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Account suspended or inactive'], 403);
        }

        $user->last_login_at = now();
        $user->save();

        // Log successful login
        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => true,
            'logged_in_at' => now(),
        ]);

        $refreshToken = hash('sha256', Str::random(64));
        UserToken::create([
            'user_id' => $user->id,
            'token' => $refreshToken,
            'type' => 'refresh',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]
        ]);
    }

    /**
     * Làm mới JWT qua refresh token
     */
    public function refresh(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);

        $record = UserToken::where('token', $request->refresh_token)
            ->where('type', 'refresh')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired refresh token'], 401);
        }

        $user = $record->user;
        $newAccessToken = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]
        ]);
    }

    /**
     * Quên mật khẩu - gửi email khôi phục
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        // Prefer OTP (6 digits) for password reset
        $otp = (string) random_int(100000, 999999);
        // Invalidate old password_reset OTPs
        OtpCode::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]);

        OtpCode::create([
            'user_id' => $user->id,
            'otp_code' => $otp,
            'type' => 'password_reset',
            'expire_at' => now()->addMinutes(10),
            'is_used' => false,
            'created_at' => now(),
        ]);

        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($otp, $user->full_name));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Reset OTP created, but failed to send email',
                'error' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset OTP sent to your email',
        ]);
    }

    /**
     * Đặt lại mật khẩu mới
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
            'otp' => 'nullable|string',
            'token' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        if (!$request->filled('otp') && !$request->filled('token')) {
            return response()->json(['status' => 'error', 'message' => 'OTP or token is required'], 422);
        }

        // Support both OTP (requires email) and token flows
        if ($request->filled('otp')) {
            if (!$request->filled('email')) {
                return response()->json(['status' => 'error', 'message' => 'Email is required with OTP'], 422);
            }
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            // Tìm OTP
            $record = OtpCode::where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('otp_code', $request->otp)
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP code'
                ], 401);
            }

            if ($record->is_used) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP has already been used'
                ], 401);
            }

            if ($record->expire_at <= now()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP has expired. Please request a new one'
                ], 401);
            }
        } else {
            $record = UserToken::where('token', $request->token)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', now())
                ->first();
            if (!$record) {
                return response()->json(['status' => 'error', 'message' => 'Invalid or expired token'], 400);
            }
            $user = $record->user;
        }

        $user->password_hash = Hash::make($request->password);
        $user->save();

        // Mark OTP as used or delete legacy token
        if ($request->filled('otp') && $record instanceof \App\Models\OtpCode) {
            $record->is_used = true;
            $record->used_at = now();
            $record->save();
        } else {
            // legacy user_tokens record
            $record->delete();
        }

        // Security: revoke all existing refresh tokens for this user
        UserToken::where('user_id', $user->id)
            ->where('type', 'refresh')
            ->delete();

        // Optional: clean up any other outstanding password_reset entries
        UserToken::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password has been reset successfully'
        ]);
    }

    /**
     * Đăng xuất - hủy token hiện tại
     */
    public function logout(Request $request)
    {
        try {
            auth('api')->logout();
        } catch (JWTException $e) {
        }

        if ($request->refresh_token) {
            UserToken::where('token', $request->refresh_token)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}
