<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'session_id',
        'min_percentage',
        'max_percentage',
        'grade',
        'gpa',
        'order_no',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'gpa' => 'decimal:2',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the session
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get grade for a percentage
     */
    public static function getGradeForPercentage(int $schoolId, ?int $sessionId, float $percentage): ?self
    {
        return self::where('school_id', $schoolId)
            ->where(function($query) use ($sessionId) {
                $query->where('session_id', $sessionId)
                    ->orWhereNull('session_id');
            })
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderBy('order_no', 'desc')
            ->first();
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_no', 'desc');
    }
}
