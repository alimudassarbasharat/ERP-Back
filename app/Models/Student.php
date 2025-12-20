<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the family information associated with the student.
     */
    public function familyInfo()
    {
        return $this->hasOne(FamilyInfo::class);
    }

    /**
     * Get the contact information associated with the student.
     */
    public function contactInfo()
    {
        return $this->hasOne(ContactInformation::class);
    }

    /**
     * Get the academic record associated with the student.
     */
    public function academicRecord()
    {
        return $this->hasOne(AcademicRecord::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id', 'id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
    
}


