<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'created_by',
        'is_archived',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_archived' => 'boolean'
    ];

    protected $appends = ['member_count', 'latest_message'];
    
    protected $with = ['creator:id,name,avatar'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_users')
            ->withPivot(['role', 'last_read_at', 'unread_count', 'is_muted', 'notification_preferences'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getMemberCountAttribute()
    {
        return $this->users()->count();
    }

    public function getLatestMessageAttribute()
    {
        return $this->latestMessage()->with('user')->first();
    }

    public function addMember(User $user, $role = 'member')
    {
        return $this->users()->attach($user->id, [
            'role' => $role,
            'unread_count' => 0,
            'is_muted' => false
        ]);
    }

    public function removeMember(User $user)
    {
        return $this->users()->detach($user->id);
    }

    public function isUserMember(User $user)
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function markAsRead(User $user)
    {
        $this->users()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
            'unread_count' => 0
        ]);
    }

    public function incrementUnreadCount(User $user)
    {
        if ($this->users()->where('user_id', $user->id)->exists()) {
            $this->users()->where('user_id', $user->id)->increment('channel_users.unread_count');
        }
    }
}