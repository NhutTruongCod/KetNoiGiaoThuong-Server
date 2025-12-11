<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

class LoginHistory extends Model
{
    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'location',
        'login_at',
        'logout_at',
        'session_duration',
        'is_successful',
        'failure_reason',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'location' => 'array',
        'session_duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

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
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    public function scopeByDevice($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->where('login_at', '>=', $from);
        }
        if ($to) {
            $query->where('login_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Parse user agent and populate device info
     */
    public static function parseUserAgent($userAgent)
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'unknown')),
            'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
            'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
        ];
    }

    /**
     * Get location from IP (placeholder - needs GeoIP service)
     */
    public static function getLocationFromIp($ip)
    {
        // TODO: Integrate with GeoIP service (MaxMind, ipapi.co, etc)
        // For now, return basic structure
        return [
            'country' => 'Vietnam',
            'city' => 'Unknown',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ];
    }

    /**
     * Calculate session duration
     */
    public function calculateSessionDuration()
    {
        if ($this->logout_at && $this->login_at) {
            $this->session_duration = $this->logout_at->diffInSeconds($this->login_at);
            $this->save();
        }
    }

    /**
     * Mark as logged out
     */
    public function markLogout()
    {
        $this->logout_at = now();
        $this->calculateSessionDuration();
        $this->save();
    }
}

