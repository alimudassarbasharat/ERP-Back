<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'reporting_number',
        'whatsapp_number',
        'email',
        'address',
        'province',
        'district',
        'city',
        'village',
        'postal_code',
        'alternative_contact_name',
        'alternative_contact_number',
        'alternative_contact_relationship'
    ];

    /**
     * Get the student that owns the contact information.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
