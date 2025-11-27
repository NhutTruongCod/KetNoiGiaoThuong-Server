<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'target_user_id',
        'target_post_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Disable updated_at since table doesn't have it
    public $timestamps = false;

    /**
     * Relationships
     */

    // Người báo cáo
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // User bị báo cáo
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // Bài viết bị báo cáo (TODO: Uncomment when TradePost model exists)
    // public function targetPost()
    // {
    //     return $this->belongsTo(TradePost::class, 'target_post_id');
    // }

    // Admin xử lý
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */

    // Báo cáo đang chờ xử lý
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Báo cáo đã xử lý
    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['action_taken', 'dismissed']);
    }

    // Báo cáo của user cụ thể
    public function scopeByReporter($query, $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    /**
     * Helper methods
     */

    // Kiểm tra có phải báo cáo user không
    public function isUserReport()
    {
        return !is_null($this->target_user_id);
    }

    // Kiểm tra có phải báo cáo post không
    public function isPostReport()
    {
        return !is_null($this->target_post_id);
    }

    // Get target type
    public function getTargetTypeAttribute()
    {
        if ($this->isUserReport()) {
            return 'user';
        }
        if ($this->isPostReport()) {
            return 'post';
        }
        return null;
    }
}
