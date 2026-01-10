<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ExamMarkStatus;

class ExamMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'class_id',
        'subject_id',
        'student_id',
        'marks_obtained',
        'teacher_id',
        'is_absent',
        'status',
        'verified_by',
        'verified_at',
        'submitted_at',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_absent' => 'boolean',
        'status' => ExamMarkStatus::class,
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
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
        return $this->belongsTo(ExamSubject::class, 'subject_id');
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher who entered the marks
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the user who verified the marks
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if marks can be edited
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Submit marks for verification
     */
    public function submitForVerification(): bool
    {
        if (!$this->status->canSubmit()) {
            return false;
        }

        $this->update([
            'status' => ExamMarkStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Verify marks
     */
    public function verify(int $verifiedBy): bool
    {
        if (!$this->status->canVerify()) {
            return false;
        }

        $this->update([
            'status' => ExamMarkStatus::VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);

        return true;
    }

    /**
     * Lock marks
     */
    public function lock(): bool
    {
        if (!$this->status->canLock()) {
            return false;
        }

        $this->update([
            'status' => ExamMarkStatus::LOCKED,
        ]);

        return true;
    }
}
