<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFeeDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_id',
        'session_id',
        'fee_head_id',
        'discount_type',
        'discount_value',
        'note',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
    ];

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

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
     * Get the fee head (nullable)
     */
    public function feeHead()
    {
        return $this->belongsTo(FeeHead::class);
    }
}
