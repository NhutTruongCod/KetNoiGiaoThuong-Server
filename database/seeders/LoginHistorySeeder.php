<?php

namespace Database\Seeders;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoginHistorySeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        $devices = ['desktop', 'mobile', 'tablet'];
        $browsers = ['Chrome 120', 'Safari 17', 'Firefox 121', 'Edge 120'];
        $oses = ['Windows 10', 'macOS 14', 'iOS 17', 'Android 14'];
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '103.1.2.3', '103.1.2.4'];
        
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        $locations = [
            ['country' => 'Vietnam', 'city' => 'Ho Chi Minh', 'timezone' => 'Asia/Ho_Chi_Minh'],
            ['country' => 'Vietnam', 'city' => 'Ha Noi', 'timezone' => 'Asia/Ho_Chi_Minh'],
            ['country' => 'Vietnam', 'city' => 'Da Nang', 'timezone' => 'Asia/Ho_Chi_Minh'],
        ];

        foreach ($users as $user) {
            // Tạo 5-10 login history cho mỗi user
            $count = rand(5, 10);
            
            for ($i = 0; $i < $count; $i++) {
                $loginAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
                $isSuccessful = rand(1, 10) > 1; // 90% successful
                
                $deviceType = $devices[array_rand($devices)];
                $browser = $browsers[array_rand($browsers)];
                $os = $oses[array_rand($oses)];
                
                $data = [
                    'user_id' => $user->id,
                    'ip_address' => $ips[array_rand($ips)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'device_type' => $deviceType,
                    'browser' => $browser,
                    'os' => $os,
                    'location' => $locations[array_rand($locations)],
                    'login_at' => $loginAt,
                    'is_successful' => $isSuccessful,
                ];

                if ($isSuccessful) {
                    // Successful login - có thể có logout
                    if (rand(1, 2) == 1) {
                        $logoutAt = $loginAt->copy()->addHours(rand(1, 8));
                        $data['logout_at'] = $logoutAt;
                        $data['session_duration'] = $logoutAt->diffInSeconds($loginAt);
                    }
                } else {
                    // Failed login
                    $failureReasons = [
                        'Invalid password',
                        'Invalid email',
                        'Account locked',
                        'Too many attempts',
                    ];
                    $data['failure_reason'] = $failureReasons[array_rand($failureReasons)];
                }

                LoginHistory::create($data);
            }
        }

        $this->command->info('Login history seeded successfully!');
    }
}
