<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ExamStatus;

class Exam extends Model
{
    use HasFactory, BelongsToMerchant;

    protected $fillable = [
        'name',
        'term',
        'term_id',
        'academic_year',
        'merchant_id',
        'created_by',
        'school_id',
        'session_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => ExamStatus::class,
    ];

    /**
     * The subjects that belong to the exam.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the term
     */
    public function examTerm()
    {
        return $this->belongsTo(ExamTerm::class, 'term_id');
    }

    /**
     * Get the session
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get exam classes
     */
    public function examClasses()
    {
        return $this->hasMany(ExamClass::class);
    }

    /**
     * Get exam papers
     */
    public function examPapers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    /**
     * Get exam marks
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class);
    }

    /**
     * Get exam results
     */
    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get exam scopes
     */
    public function examScopes()
    {
        return $this->hasMany(ExamScope::class);
    }

    /**
     * Get datesheets
     */
    public function datesheets()
    {
        return $this->hasMany(ExamDatesheet::class);
    }

    /**
     * Get active datesheet
     */
    public function activeDatesheet()
    {
        return $this->hasOne(ExamDatesheet::class)->where('status', 'published')->latest();
    }

    /**
     * Check if exam can be edited
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Lock exam
     */
    public function lock(): bool
    {
        if (!$this->status->canLock()) {
            return false;
        }

        $this->update([
            'status' => ExamStatus::LOCKED,
        ]);

        return true;
    }

    /**
     * Check if all papers are approved
     */
    public function allPapersApproved(): bool
    {
        return $this->examPapers()
            ->where('status', '!=', \App\Enums\ExamPaperStatus::APPROVED)
            ->where('status', '!=', \App\Enums\ExamPaperStatus::LOCKED)
            ->count() === 0;
    }

    /**
     * Check if all marks are verified
     */
    public function allMarksVerified(): bool
    {
        return $this->examMarks()
            ->where('status', '!=', \App\Enums\ExamMarkStatus::VERIFIED)
            ->where('status', '!=', \App\Enums\ExamMarkStatus::LOCKED)
            ->count() === 0;
    }

    /**
     * Check if exam is ready to publish
     */
    public function isReadyToPublish(): bool
    {
        return $this->status === ExamStatus::LOCKED 
            && $this->allPapersApproved() 
            && $this->allMarksVerified()
            && $this->examResults()->where('status', 'published')->count() === 0;
    }
}
