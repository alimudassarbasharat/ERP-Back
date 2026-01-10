<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'frequency',
    ];

    protected $casts = [
        'frequency' => 'string',
    ];

    /**
     * Get the school that owns this fee head
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all fee structures using this fee head
     */
    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * Get all student fee discounts for this fee head
     */
    public function studentFeeDiscounts()
    {
        return $this->hasMany(StudentFeeDiscount::class);
    }
}
