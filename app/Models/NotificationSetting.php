<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'enable_whatsapp',
        'enable_sms',
        'enable_email',
        'days_before_due',
        'days_after_due',
        'message_template',
    ];

    protected $casts = [
        'enable_whatsapp' => 'boolean',
        'enable_sms' => 'boolean',
        'enable_email' => 'boolean',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
