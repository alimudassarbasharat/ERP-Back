<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all exam papers for this subject
     */
    public function examPapers()
    {
        return $this->hasMany(ExamPaper::class, 'subject_id');
    }

    /**
     * Get all exam marks for this subject
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class, 'subject_id');
    }
}
