<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Cầu nối gửi thông báo:
 * - Controller/Listener chỉ cần gọi 1 chỗ.
 * - Thay đổi kênh/format sau này chỉ sửa tại đây.
 */
class NotificationBridge
{
    /** Gửi 1 Notification Laravel có sẵn tới user(s). */
    public function to($notifiables, BaseNotification $notification): void
    {
        Notification::send($notifiables, $notification);
    }
}
