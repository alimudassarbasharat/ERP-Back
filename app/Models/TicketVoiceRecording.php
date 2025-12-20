<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketVoiceRecording extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'comment_id',
        'user_id',
        'file_path',
        'file_name',
        'duration',
        'file_size',
        'mime_type',
        'transcription'
    ];

    protected $casts = [
        'duration' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = ['formatted_duration', 'file_url'];

    /**
     * Relationships
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function comment()
    {
        return $this->belongsTo(TicketComment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) return '0:00';
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get file URL with full backend URL
     */
    public function getFileUrlAttribute()
    {
        // Get backend URL from config
        $backendUrl = config('app.url');
        
        // Return full URL
        return $backendUrl . '/storage/' . $this->file_path;
    }
}

