<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'ticket_number',
        'workspace_id',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'reporter_id',
        'assignee_id',
        'total_time_tracked',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'total_time_tracked' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = ['formatted_time_tracked'];

    /**
     * Boot method to auto-generate ticket number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber($ticket->merchant_id);
            }
        });
    }

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber($merchantId)
    {
        $lastTicket = self::where('merchant_id', $merchantId)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastTicket ? intval(substr($lastTicket->ticket_number, 4)) + 1 : 1;
        return 'TKT-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function subtasks()
    {
        return $this->hasMany(TicketSubtask::class)->orderBy('position');
    }

    public function activities()
    {
        return $this->hasMany(TicketActivity::class)->orderBy('created_at', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function timeLogs()
    {
        return $this->hasMany(TicketTimeLog::class)->orderBy('started_at', 'desc');
    }

    public function voiceRecordings()
    {
        return $this->hasMany(TicketVoiceRecording::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get formatted time tracked
     */
    public function getFormattedTimeTrackedAttribute()
    {
        $seconds = $this->total_time_tracked;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }

    /**
     * Get subtasks completion percentage
     */
    public function getSubtasksProgressAttribute()
    {
        $total = $this->subtasks()->count();
        if ($total === 0) return 0;
        
        $completed = $this->subtasks()->where('completed', true)->count();
        return round(($completed / $total) * 100);
    }

    /**
     * Scopes
     */
    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    public function scopeReportedBy($query, $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    /**
     * Log activity
     */
    public function logActivity($type, $description, $metadata = null)
    {
        return $this->activities()->create([
            'merchant_id' => $this->merchant_id,
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'metadata' => $metadata
        ]);
    }
}
