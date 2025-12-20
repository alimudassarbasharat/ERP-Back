<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'father_name',
        'father_cnic',
        'father_occupation',
        'mother_name',
        'mother_cnic',
        'mother_occupation',
        'guardian_name',
        'guardian_cnic',
        'guardian_occupation',
        'guardian_relationship',
        'home_address',
        'emergency_contact',
        'monthly_income',
        'family_members'
    ];

    /**
     * Get the student that owns the family information.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
