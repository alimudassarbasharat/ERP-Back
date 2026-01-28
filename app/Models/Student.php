<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes, BelongsToMerchant;

    protected $guarded = [];

    // protected static function booted()
    // {
    //     static::addGlobalScope('merchant', function ($query) {
    //         $query->where('students.merchant_id', auth()->user()->merchant_id);
    //     });
    // }

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

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the current class
     */
    public function currentClass()
    {
        return $this->belongsTo(Classes::class, 'current_class_id');
    }

    /**
     * Get the current session
     */
    public function currentSession()
    {
        return $this->belongsTo(Session::class, 'current_session_id');
    }

    /**
     * Get family groups this student belongs to
     */
    public function familyGroups()
    {
        return $this->belongsToMany(FamilyGroup::class, 'family_students', 'student_id', 'family_group_id')
            ->withTimestamps();
    }

    /**
     * Get fee plans for this student
     */
    public function feePlans()
    {
        return $this->hasMany(StudentFeePlan::class);
    }

    /**
     * Get fee discounts for this student
     */
    public function feeDiscounts()
    {
        return $this->hasMany(StudentFeeDiscount::class);
    }

    /**
     * Get fee invoices for this student
     */
    public function feeInvoices()
    {
        return $this->hasMany(FeeInvoice::class);
    }

    /**
     * Get exam marks for this student
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class);
    }

    /**
     * Get exam results for this student
     */
    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }
}
