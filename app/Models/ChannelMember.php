<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
        'preferences'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'preferences' => 'array'
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_muted', false);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
} 