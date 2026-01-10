<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DirectMessageConversation extends Model
{
    use TenantScope;

    protected $fillable = [
        'name',
        'is_group',
        'merchant_id'
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
        // Increment unread_count in the pivot table for all participants except the sender
        // Use DB::table to directly update the pivot table
        DB::table('direct_message_participants')
            ->where('conversation_id', $this->id)
            ->where('user_id', '!=', $user->id)
            ->increment('unread_count');
    }

    public static function findOrCreateBetweenUsers($user1Id, $user2Id, $merchantId = null)
    {
        // Get merchant_id if not provided
        if (!$merchantId) {
            $user = User::find($user1Id);
            $merchantId = $user->merchant_id ?? self::getCurrentMerchantId();
        }

        // Find existing conversation between two users within the same merchant
        $conversation = self::whereHas('participants', function ($query) use ($user1Id) {
                $query->where('user_id', $user1Id);
            })
            ->whereHas('participants', function ($query) use ($user2Id) {
                $query->where('user_id', $user2Id);
            })
            ->where('is_group', false)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$conversation) {
            $conversation = self::create([
                'is_group' => false,
                'merchant_id' => $merchantId
            ]);
            $conversation->participants()->attach([$user1Id, $user2Id]);
        }

        return $conversation;
    }

    /**
     * Get current merchant_id (helper method)
     */
    protected static function getCurrentMerchantId()
    {
        $user = auth()->user();
        if ($user && isset($user->merchant_id)) {
            return $user->merchant_id;
        }
        if (request() && request()->attributes->has('merchant_id')) {
            return request()->attributes->get('merchant_id');
        }
        return null;
    }
}