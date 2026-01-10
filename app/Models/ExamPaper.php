<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ExamPaperStatus;
use App\Traits\TenantScope;

class ExamPaper extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'merchant_id',
        'exam_id',
        'class_id',
        'subject_id',
        'school_id',
        'title',
        'instructions',
        'total_marks',
        'passing_marks',
        'duration_minutes',
        'exam_date',
        'status',
        'paper_version',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
        'paper_pdf_path',
        'created_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'reviewed_at' => 'datetime',
        'status' => ExamPaperStatus::class,
    ];

    /**
     * Get the exam
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the class
     */
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the subject
     */
    public function subject()
    {
        // Try Subject first, fallback to ExamSubject if needed
        if (class_exists(\App\Models\Subject::class)) {
            return $this->belongsTo(\App\Models\Subject::class, 'subject_id');
        }
        return $this->belongsTo(ExamSubject::class, 'subject_id');
    }

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created this paper
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for createdBy
     */
    public function creator()
    {
        return $this->createdBy();
    }

    /**
     * Get the user who reviewed this paper
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Alias for reviewedBy
     */
    public function reviewer()
    {
        return $this->reviewedBy();
    }

    /**
     * Get all questions for this paper
     */
    public function questions()
    {
        return $this->hasMany(ExamQuestion::class)->ordered();
    }

    /**
     * Check if paper can be edited
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Submit paper for review
     */
    public function submitForReview(): bool
    {
        if (!$this->status->canSubmit()) {
            return false;
        }

        $this->update([
            'status' => ExamPaperStatus::SUBMITTED,
        ]);

        return true;
    }

    /**
     * Approve paper
     */
    public function approve(int $reviewedBy, ?string $comment = null): bool
    {
        if (!$this->status->canApprove()) {
            return false;
        }

        $this->update([
            'status' => ExamPaperStatus::APPROVED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);

        return true;
    }

    /**
     * Reject paper
     */
    public function reject(int $reviewedBy, string $comment): bool
    {
        if (!$this->status->canApprove()) {
            return false;
        }

        $this->update([
            'status' => ExamPaperStatus::REJECTED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);

        return true;
    }

    /**
     * Lock paper
     */
    public function lock(): bool
    {
        if (!$this->status->canLock()) {
            return false;
        }

        $this->update([
            'status' => ExamPaperStatus::LOCKED,
        ]);

        return true;
    }

    /**
     * Calculate total marks from questions
     */
    public function calculateTotalMarks(): float
    {
        return $this->questions()->sum('marks');
    }

    /**
     * Auto-update total marks when questions change
     */
    public function updateTotalMarks(): void
    {
        $totalMarks = $this->calculateTotalMarks();
        $this->update(['total_marks' => $totalMarks]);
    }
}
