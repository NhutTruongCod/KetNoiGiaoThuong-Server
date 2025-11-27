<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'format',
        'status',
        'download_url',
        'requested_at',
        'completed_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

