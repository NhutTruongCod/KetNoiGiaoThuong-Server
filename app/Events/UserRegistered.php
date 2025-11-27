
<?php


use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;   // giúp gọi event() dễ
use Illuminate\Queue\SerializesModels;          // tuần tự hoá qua queue

class UserRegistered
{
    use Dispatchable, SerializesModels;

    /** @var \App\Models\User Người dùng vừa đăng ký */
    public User $user;

    /**
     * Sự kiện mang đúng dữ liệu cần thiết, không xử lý logic ở đây.
     */
    public function __construct(User $user)
    {
        $this->user = $user; // bất biến, chỉ đọc
    }
}