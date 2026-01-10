<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'merchant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Messaging relationships
    public function channels()
    {
        // CRITICAL FIX: Query channels through channel_users pivot table
        // This ensures members ALWAYS see channels they belong to
        $merchantId = $this->merchant_id ?? 'DEFAULT_TENANT';
        
        return $this->belongsToMany(Channel::class, 'channel_users', 'user_id', 'channel_id')
                    ->where('channels.merchant_id', $merchantId) // Tenant scope
                    ->where('channels.is_archived', false) // Exclude archived
                    ->withPivot(['role', 'last_read_at', 'unread_count', 'is_muted', 'notification_preferences'])
                    ->withTimestamps()
                    ->orderBy('channels.updated_at', 'desc');
    }

    public function createdChannels()
    {
        return $this->hasMany(Channel::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function directMessages()
    {
        return $this->hasMany(DirectMessage::class);
    }

    public function directMessageConversations()
    {
        return $this->belongsToMany(DirectMessageConversation::class, 'direct_message_participants', 'user_id', 'conversation_id')
                    ->withPivot(['last_read_at', 'unread_count', 'is_muted'])
                    ->withTimestamps();
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function presence()
    {
        return $this->hasOne(UserPresence::class);
    }

    public function getUnreadMessagesCount()
    {
        $channelUnread = $this->channels()->sum('channel_users.unread_count');
        $dmUnread = $this->directMessageConversations()->sum('direct_message_participants.unread_count');
        return $channelUnread + $dmUnread;
    }

    public function isOnline()
    {
        return $this->presence && $this->presence->isOnline();
    }

    public function getStatus()
    {
        return $this->presence ? $this->presence->status : 'offline';
    }

    public function getStatusText()
    {
        return $this->presence ? $this->presence->status_text : null;
    }
}
