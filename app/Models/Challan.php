<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challan extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'fee_invoice_id',
        'challan_no',
        'amount',
        'due_date',
        'status',
        'pdf_path',
        'pdf_status',
        'pdf_generated_at',
        'parent_challan_id',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'student_snapshot',
        'class_snapshot',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'cancelled_at' => 'datetime',
        'generated_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'student_snapshot' => 'array',
        'class_snapshot' => 'array',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the fee invoice
     */
    public function feeInvoice()
    {
        return $this->belongsTo(FeeInvoice::class);
    }

    /**
     * Get the parent challan (for reissue/supplementary)
     */
    public function parentChallan()
    {
        return $this->belongsTo(Challan::class, 'parent_challan_id');
    }

    /**
     * Get child challans (reissues/supplementary)
     */
    public function childChallans()
    {
        return $this->hasMany(Challan::class, 'parent_challan_id');
    }

    /**
     * Get the user who generated this challan
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the user who cancelled this challan
     */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get all payments for this challan
     */
    public function payments()
    {
        return $this->hasMany(ChallanPayment::class);
    }

    /**
     * Check if challan can be regenerated (should return false - immutable)
     */
    public function canRegenerate(): bool
    {
        return false; // Challans are immutable
    }
}
