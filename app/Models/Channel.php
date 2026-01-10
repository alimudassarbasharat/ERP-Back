<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Channel extends Model
{
    use TenantScope;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'created_by',
        'is_archived',
        'settings',
        'merchant_id'
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
        // CRITICAL FIX: Ensure tenant scoping in relationship
        // Only return users that belong to the same merchant_id as the channel
        return $this->belongsToMany(User::class, 'channel_users')
            ->where('users.merchant_id', $this->merchant_id ?? 'DEFAULT_TENANT')
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

    public function incrementUnreadCount(User $excludeUser)
    {
        // CRITICAL FIX: Increment unread_count in the pivot table (channel_users), not users table
        // Use DB::table to directly update the pivot table (same pattern as DirectMessageConversation)
        DB::table('channel_users')
            ->where('channel_id', $this->id)
            ->where('user_id', '!=', $excludeUser->id)
            ->where('is_muted', false)
            ->increment('unread_count');
    }
}