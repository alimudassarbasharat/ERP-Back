<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'type',
        'reference_type',
        'reference_id',
        'trigger',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all notification channels for this event
     */
    public function channels()
    {
        return $this->hasMany(NotificationChannel::class, 'event_id');
    }

    /**
     * Get the polymorphic reference (e.g., Challan)
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
