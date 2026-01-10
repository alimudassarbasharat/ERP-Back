<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScope;

class ExamMarksheetConfig extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'merchant_id',
        'exam_id',
        'school_id',
        'class_ids',
        'subject_ids',
        'total_marks_mode',
        'total_marks',
        'subject_totals',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'class_ids' => 'array',
        'subject_ids' => 'array',
        'subject_totals' => 'array',
    ];

    /**
     * Get the exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the school
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get total marks for a subject
     */
    public function getTotalMarksForSubject($subjectId): ?int
    {
        if ($this->total_marks_mode === 'same_for_all') {
            return $this->total_marks;
        }
        
        return $this->subject_totals[$subjectId] ?? null;
    }
}
