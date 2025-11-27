<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentityVerificationRequest extends Model
{
    use HasFactory;

    protected $table = 'identity_verification_requests';

    protected $fillable = [
        'user_id',
        'document_type',
        'document_url',
        'status',
        'admin_note',
        'approved_by',
    ];

    /**
     * Quan hệ: một yêu cầu xác minh thuộc về một user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Quan hệ: admin duyệt (nếu có)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
