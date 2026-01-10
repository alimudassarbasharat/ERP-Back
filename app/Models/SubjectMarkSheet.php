<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScope;

class SubjectMarkSheet extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $table = 'subject_mark_sheets';

    protected $fillable = [
        'merchant_id',
        'student_id',
        'subject_id',
        'exam_id',
        'class_id',
        'section_id',
        'academic_year_id',
        'marks_obtained',
        'max_marks',
        'min_marks',
        'grade',
        'grade_points',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'max_marks' => 'decimal:2',
        'min_marks' => 'decimal:2',
        'grade_points' => 'decimal:2',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
} 