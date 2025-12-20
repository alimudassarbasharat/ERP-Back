<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'user_id',
        'parent_id',
        'comment',
        'is_internal'
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Boot method to log activity
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($comment) {
            $comment->ticket->logActivity(
                'comment_added',
                auth()->user()->name . ' added a comment',
                ['comment_id' => $comment->id]
            );
        });
    }

    /**
     * Relationships
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(TicketComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketCommentAttachment::class, 'comment_id')->orderBy('created_at', 'asc');
    }

    public function voiceRecordings()
    {
        return $this->hasMany(TicketVoiceRecording::class, 'comment_id')->orderBy('created_at', 'asc');
    }
}
