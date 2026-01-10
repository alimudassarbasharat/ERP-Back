<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Message extends Model
{
    use TenantScope;

    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'parent_id',
        'is_edited',
        'edited_at',
        'is_deleted',
        'merchant_id'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime'
    ];

    protected $appends = ['reactions_summary', 'reply_count'];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(MessageReaction::class, 'reactable');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function getReactionsSummaryAttribute()
    {
        return $this->reactions()
            ->select('emoji', \DB::raw('count(*) as count'))
            ->groupBy('emoji')
            ->get()
            ->map(function ($reaction) {
                $reaction->users = $this->reactions()
                    ->where('emoji', $reaction->emoji)
                    ->with('user:id,name')
                    ->get()
                    ->pluck('user');
                return $reaction;
            });
    }

    public function getReplyCountAttribute()
    {
        return $this->replies()->count();
    }

    public function addReaction($emoji, User $user)
    {
        return $this->reactions()->firstOrCreate([
            'user_id' => $user->id,
            'emoji' => $emoji
        ]);
    }

    public function removeReaction($emoji, User $user)
    {
        return $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->delete();
    }

    public function markAsEdited($newContent)
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now()
        ]);
    }

    public function markAsDeleted()
    {
        $this->update([
            'is_deleted' => true,
            'content' => 'This message has been deleted'
        ]);
    }
}