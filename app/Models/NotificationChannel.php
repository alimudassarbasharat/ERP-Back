<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'channel',
        'recipient',
        'status',
        'provider_response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the notification event
     */
    public function event()
    {
        return $this->belongsTo(NotificationEvent::class, 'event_id');
    }
}
