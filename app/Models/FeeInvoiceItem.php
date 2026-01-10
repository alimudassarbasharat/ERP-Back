<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_invoice_id',
        'fee_head_name',
        'fee_head_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the fee invoice
     */
    public function feeInvoice()
    {
        return $this->belongsTo(FeeInvoice::class);
    }

    /**
     * Get the fee head (nullable, snapshot stored in fee_head_name)
     */
    public function feeHead()
    {
        return $this->belongsTo(FeeHead::class);
    }
}
