<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'status_message',
        'last_seen_at',
        'presence_data'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'presence_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAway($query)
    {
        return $query->where('status', 'away');
    }

    public function scopeBusy($query)
    {
        return $query->where('status', 'busy');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
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