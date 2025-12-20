<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'user_id',
        'started_at',
        'stopped_at',
        'duration',
        'notes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['formatted_duration'];

    /**
     * Boot method to log activity and update ticket
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($timeLog) {
            $timeLog->ticket->logActivity(
                'timer_started',
                auth()->user()->name . ' started timer'
            );
        });

        static::updated(function ($timeLog) {
            if ($timeLog->stopped_at && $timeLog->duration) {
                // Update total time on ticket
                $ticket = $timeLog->ticket;
                $ticket->increment('total_time_tracked', $timeLog->duration);

                // Log activity with duration in seconds
                $timeLog->ticket->logActivity(
                    'timer_stopped',
                    auth()->user()->name . ' stopped timer',
                    [
                        'time_logged' => $timeLog->duration, // Store as seconds
                        'duration' => $timeLog->duration     // Also as 'duration' key for compatibility
                    ]
                );
            }
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

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) return '0h 0m';
        
        $seconds = $this->duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }

    /**
     * Stop the timer
     */
    public function stop()
    {
        if ($this->stopped_at) {
            return false;
        }

        $duration = now()->diffInSeconds($this->started_at);
        
        $this->update([
            'stopped_at' => now(),
            'duration' => $duration
        ]);

        return true;
    }

    /**
     * Scope to get active timers
     */
    public function scopeActive($query)
    {
        return $query->whereNull('stopped_at');
    }
}
