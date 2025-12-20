<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $table = 'fee_payments';
    protected $guarded = [];

    /**
     * Get the fee summary that owns the payment.
     */
    public function feeSummary()
    {
        return $this->belongsTo(FeeSummary::class, 'fee_summary_id');
    }
} 