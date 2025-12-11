<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DataExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'format',
        'data_types',
        'date_from',
        'date_to',
        'include_deleted',
        'status',
        'progress',
        'current_step',
        'download_url',
        'file_name',
        'file_size',
        'downloads_count',
        'max_downloads',
        'requested_at',
        'estimated_completion',
        'completed_at',
        'expires_at',
        'expired_at',
        'cancelled_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'data_types' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
        'include_deleted' => 'boolean',
        'progress' => 'integer',
        'file_size' => 'integer',
        'downloads_count' => 'integer',
        'max_downloads' => 'integer',
        'requested_at' => 'datetime',
        'estimated_completion' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Accessors
     */
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Methods
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canDownload()
    {
        return $this->status === 'completed' 
            && !$this->isExpired() 
            && $this->downloads_count < $this->max_downloads;
    }

    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }

    public function markAsProcessing($step = null)
    {
        $this->update([
            'status' => 'processing',
            'current_step' => $step,
        ]);
    }

    public function updateProgress($progress, $step = null)
    {
        $data = ['progress' => $progress];
        if ($step) {
            $data['current_step'] = $step;
        }
        $this->update($data);
    }

    public function markAsCompleted($fileName, $fileSize, $downloadUrl)
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'download_url' => $downloadUrl,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired',
            'expired_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}

