<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TeacherPersonalDetail;
use App\Models\TeacherContactDetail;
use App\Models\TeacherProfessionalDetail;
use App\Models\TeacherAdditionalDetail;

class Teacher extends Model
{
    use HasFactory, SoftDeletes, BelongsToMerchant;

protected $fillable = [
    'user_id',
    'employee_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'qualification',
    'experience',
    'department_id',
    'designation',
    'joining_date',
    'is_active',
    'employee_code',
    'dob',
    'gender',
    'marital_status',
    'national_id',
    'emergency_contact',
    'salary',
    'bank_account',
    'shift',
    'contract_type',
    'resignation_date',
    'profile_photo',
    'created_at',
    'updated_at',
    'password',
    'username',
];


    protected $casts = [
        'joining_date' => 'date',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalDetails(): HasOne
    {
        return $this->hasOne(TeacherPersonalDetail::class);
    }

    public function contactDetails(): HasOne
    {
        return $this->hasOne(TeacherContactDetail::class);
    }

    public function professionalDetails(): HasOne
    {
        return $this->hasOne(TeacherProfessionalDetail::class);
    }

    public function additionalDetails(): HasOne
    {
        return $this->hasOne(TeacherAdditionalDetail::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects', 'teacher_id', 'subject_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classes::class, 'teacher_classes', 'teacher_id', 'class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
