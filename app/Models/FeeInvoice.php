<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    use HasFactory, BelongsToMerchant;

    protected $fillable = [
        'school_id',
        'student_id',
        'session_id',
        'billing_month',
        'subtotal',
        'discount_total',
        'total_amount',
        'status',
        'due_date',
        'generated_at',
        'pdf_path',
        'pdf_status',
        'pdf_generated_at',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'generated_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get all invoice items (snapshot)
     */
    public function items()
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }

    /**
     * Get all challans for this invoice
     */
    public function challans()
    {
        return $this->hasMany(Challan::class);
    }
}
