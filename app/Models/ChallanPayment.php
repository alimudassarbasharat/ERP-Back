<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'challan_id',
        'school_id',
        'payment_method',
        'transaction_ref',
        'paid_amount',
        'paid_at',
        'provider_payload',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'provider_payload' => 'array',
    ];

    /**
     * Get the challan
     */
    public function challan()
    {
        return $this->belongsTo(Challan::class);
    }

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
