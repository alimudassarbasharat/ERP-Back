<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'user_id',
        'activity_type',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

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

    /**
     * Get voice recording for voice message activities
     */
    public function voiceRecording()
    {
        if ($this->activity_type === 'voice_message_sent' && isset($this->metadata['voice_recording_id'])) {
            return TicketVoiceRecording::find($this->metadata['voice_recording_id']);
        }
        return null;
    }

    /**
     * Append voice recording to array representation
     */
    protected $appends = ['voice_recording_data'];

    public function getVoiceRecordingDataAttribute()
    {
        return $this->voiceRecording();
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope by activity type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }
}
