<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectMessageConversation extends Model
{
    protected $fillable = [
        'name',
        'is_group'
    ];

    protected $casts = [
        'is_group' => 'boolean'
    ];

    protected $appends = ['latest_message', 'display_name'];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'direct_message_participants', 'conversation_id', 'user_id')
            ->withPivot(['last_read_at', 'unread_count', 'is_muted'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(DirectMessage::class, 'conversation_id')->latest();
    }

    public function getLatestMessageAttribute()
    {
        return $this->latestMessage()->with('user')->first();
    }

    public function getDisplayNameAttribute()
    {
        if ($this->is_group) {
            return $this->name ?: $this->participants->pluck('name')->implode(', ');
        }
        
        // For 1-on-1 conversations, return the other participant's name
        $currentUserId = auth()->id();
        $otherParticipant = $this->participants->where('id', '!=', $currentUserId)->first();
        
        return $otherParticipant ? $otherParticipant->name : 'Unknown User';
    }

    public function addParticipant(User $user)
    {
        return $this->participants()->attach($user->id, [
            'unread_count' => 0,
            'is_muted' => false
        ]);
    }

    public function removeParticipant(User $user)
    {
        return $this->participants()->detach($user->id);
    }

    public function markAsRead(User $user)
    {
        $this->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
            'unread_count' => 0
        ]);
    }

    public function incrementUnreadCount(User $user)
    {
        $this->participants()
            ->where('user_id', '!=', $user->id)
            ->increment('direct_message_participants.unread_count');
    }

    public static function findOrCreateBetweenUsers($user1Id, $user2Id)
    {
        // Find existing conversation between two users
        $conversation = self::whereHas('participants', function ($query) use ($user1Id) {
                $query->where('user_id', $user1Id);
            })
            ->whereHas('participants', function ($query) use ($user2Id) {
                $query->where('user_id', $user2Id);
            })
            ->where('is_group', false)
            ->first();

        if (!$conversation) {
            $conversation = self::create(['is_group' => false]);
            $conversation->participants()->attach([$user1Id, $user2Id]);
        }

        return $conversation;
    }
}