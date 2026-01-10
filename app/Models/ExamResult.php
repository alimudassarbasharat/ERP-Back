<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_id',
        'total_obtained',
        'total_marks',
        'percentage',
        'grade',
        'rank_in_class',
        'result_snapshot_json',
        'marksheet_pdf_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'total_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'result_snapshot_json' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the exam
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
