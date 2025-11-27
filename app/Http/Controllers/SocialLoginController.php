<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use GuzzleHttp\Client;

/**
 * API Social Login (Google)
 *
 * FE sẽ gửi id_token (Google One Tap / Google Sign In).
 * Backend verify token với Google, sau đó tạo/đăng nhập user + phát Sanctum token.
 *
 * POST /api/auth/social/google
 * body: { id_token: string }
 */
class SocialLoginController extends Controller
{
    public function google(Request $request)
    {
        $v = $request->validate([
            'id_token' => 'required|string',
        ]);

        $client = new Client([
            'timeout' => 5.0,
        ]);

        // Verify token với Google
        $resp = $client->get('https://oauth2.googleapis.com/tokeninfo', [
            'query' => ['id_token' => $v['id_token']],
        ]);

        $data = json_decode((string) $resp->getBody(), true);

        if (empty($data['email']) || ($data['email_verified'] ?? 'false') !== 'true') {
            return response()->json(['message' => 'Token Google không hợp lệ'], 422);
        }

        $email = $data['email'];
        $name  = $data['name'] ?? explode('@', $email)[0];

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make(Str::random(32)),
            ]
        );

        // Tạo Sanctum token để FE gọi API
        $token = $user->createToken('google')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }
}
