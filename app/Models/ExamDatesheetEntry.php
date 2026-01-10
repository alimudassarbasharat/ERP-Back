<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamDatesheetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'datesheet_id',
        'exam_id',
        'class_id',
        'section_id',
        'subject_id',
        'exam_date',
        'start_time',
        'end_time',
        'room_id',
        'room_name',
        'supervisor_id',
        'invigilator_id',
        'paper_id',
        'total_marks',
        'instructions',
        'has_conflict',
        'conflict_details',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'has_conflict' => 'boolean',
        'conflict_details' => 'array',
    ];

    /**
     * Get the datesheet
     */
    public function datesheet()
    {
        return $this->belongsTo(ExamDatesheet::class, 'datesheet_id');
    }

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
     * Get the section
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the subject
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the supervisor
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the invigilator
     */
    public function invigilator()
    {
        return $this->belongsTo(User::class, 'invigilator_id');
    }

    /**
     * Get the paper
     */
    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'paper_id');
    }
}
