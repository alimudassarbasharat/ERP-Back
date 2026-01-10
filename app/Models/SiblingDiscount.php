<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiblingDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'session_id',
        'min_siblings',
        'discount_type',
        'discount_value',
        'apply_on',
        'specific_fee_head_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
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
     * Get the specific fee head (nullable)
     */
    public function specificFeeHead()
    {
        return $this->belongsTo(FeeHead::class, 'specific_fee_head_id');
    }
}
