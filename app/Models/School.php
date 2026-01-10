<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes, BelongsToMerchant;

    protected $fillable = [
        // Core Identity
        'name',
        'code',
        'logo',
        'school_type',
        'tagline',
        
        // Contact Information
        'phone_primary',
        'phone_secondary',
        'email',
        'website',
        
        // Address Information
        'country',
        'state_province',
        'city',
        'address_line_1',
        'address_line_2',
        'postal_code',
        
        // Branding/Theme
        'primary_color',
        'secondary_color',
        'invoice_footer_text',
        'report_header_text',
        
        // Academic Defaults
        'timezone',
        'currency',
        'date_format',
        'week_start_day',
        
        // Communication Settings
        'default_whatsapp_sender',
        'default_sms_sender',
        'default_email_sender_name',
        'notification_channels_enabled',
        
        // Security/Audit
        'created_by',
        'updated_by',
        'completed_at',
        'merchant_id'
    ];

    protected $casts = [
        'notification_channels_enabled' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Check if school profile is complete
     */
    public function isProfileComplete(): bool
    {
        $requiredFields = [
            'name', 'code', 'phone_primary', 'email', 'city', 'address_line_1'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($this->{$field})) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get profile completion percentage
     */
    public function getProfileCompletionPercentage(): int
    {
        $allFields = [
            'name', 'code', 'logo', 'school_type', 'tagline',
            'phone_primary', 'phone_secondary', 'email', 'website',
            'country', 'state_province', 'city', 'address_line_1', 'address_line_2', 'postal_code',
            'primary_color', 'secondary_color', 'invoice_footer_text', 'report_header_text',
            'timezone', 'currency', 'date_format', 'week_start_day',
            'default_whatsapp_sender', 'default_sms_sender', 'default_email_sender_name',
            'notification_channels_enabled'
        ];
        
        $filledFields = 0;
        foreach ($allFields as $field) {
            if (!empty($this->{$field})) {
                $filledFields++;
            }
        }
        
        return round(($filledFields / count($allFields)) * 100);
    }

    /**
     * Mark profile as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Get all sessions for this school
     */
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Get active session
     */
    public function activeSession()
    {
        return $this->sessions()->where('is_active', true)->first();
    }

    /**
     * Get all classes for this school
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /**
     * Get all students for this school
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get all fee heads for this school
     */
    public function feeHeads()
    {
        return $this->hasMany(FeeHead::class);
    }

    /**
     * Get all fee structures for this school
     */
    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * Get all exams for this school
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get notification settings for this school
     */
    public function notificationSettings()
    {
        return $this->hasOne(NotificationSetting::class);
    }
}
