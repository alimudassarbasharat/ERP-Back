<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresence extends Model
{
    protected $table = 'user_presence';

    protected $fillable = [
        'user_id',
        'status',
        'status_text',
        'last_seen_at'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function updateUserStatus($userId, $status, $statusText = null)
    {
        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'status' => $status,
                'status_text' => $statusText,
                'last_seen_at' => now()
            ]
        );
    }

    public function isOnline()
    {
        return $this->status === 'online';
    }

    public function isAway()
    {
        return $this->status === 'away';
    }

    public function isBusy()
    {
        return $this->status === 'busy';
    }

    public function isOffline()
    {
        return $this->status === 'offline';
    }
}