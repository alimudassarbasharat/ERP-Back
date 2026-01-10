<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamDatesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'school_id',
        'status',
        'created_by',
        'published_by',
        'published_at',
        'conflict_count',
        'notes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the exam
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the publisher
     */
    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get all entries
     */
    public function entries()
    {
        return $this->hasMany(ExamDatesheetEntry::class, 'datesheet_id');
    }

    /**
     * Check if datesheet can be published
     */
    public function canPublish(): bool
    {
        return $this->status === 'draft' && $this->conflict_count === 0;
    }
}
