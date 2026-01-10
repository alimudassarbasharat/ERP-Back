<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeSummary extends Model
{
    protected $table = 'fee_summaries';
    protected $guarded = [];

    /**
     * Get the payments associated with the fee summary.
     */
    public function payments()
    {
        return $this->hasMany(FeePayment::class, 'fee_summary_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    
    public function class()
    {
        return $this->belongsTo(Classes::class);
    }
} 