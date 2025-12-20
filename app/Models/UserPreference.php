<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preference_type',
        'preference_key',
        'preference_value',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by preference type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('preference_type', $type);
    }

    /**
     * Get user preferences by type and format as key-value pairs.
     */
    public static function getUserPreferences($userId, $type = 'report_template')
    {
        return static::where('user_id', $userId)
            ->where('preference_type', $type)
            ->pluck('preference_value', 'preference_key')
            ->toArray();
    }

    /**
     * Set user preference.
     */
    public static function setUserPreference($userId, $type, $key, $value, $metadata = null)
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'preference_type' => $type,
                'preference_key' => $key
            ],
            [
                'preference_value' => $value,
                'metadata' => $metadata
            ]
        );
    }
} 