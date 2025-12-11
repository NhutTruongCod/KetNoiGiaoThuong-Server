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
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'evidence_images',
        'status',
        'resolution',
        'action_taken',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Enable timestamps
    public $timestamps = true;
    
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';

    /**
     * Relationships
     */

    // Người báo cáo
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // User bị báo cáo (legacy)
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // Admin xử lý
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Polymorphic relationship - Đối tượng bị báo cáo
    public function reportable()
    {
        return $this->morphTo();
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
        return $query->where('status', 'resolved');
    }

    // Báo cáo bị dismissed
    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    // Báo cáo của user cụ thể
    public function scopeByReporter($query, $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    // Lọc theo reportable type
    public function scopeByReportableType($query, $type)
    {
        return $query->where('reportable_type', $type);
    }

    // Lọc theo reason
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Helper methods
     */

    // Kiểm tra có phải báo cáo user không (legacy)
    public function isUserReport()
    {
        return !is_null($this->target_user_id);
    }

    // Kiểm tra có phải báo cáo post không (legacy)
    public function isPostReport()
    {
        return !is_null($this->target_post_id);
    }

    // Get target type (legacy support)
    public function getTargetTypeAttribute()
    {
        if ($this->reportable_type) {
            return $this->reportable_type;
        }
        
        if ($this->isUserReport()) {
            return 'user';
        }
        if ($this->isPostReport()) {
            return 'post';
        }
        return null;
    }
}
