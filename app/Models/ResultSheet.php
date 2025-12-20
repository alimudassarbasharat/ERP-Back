<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultSheet extends Model
{
    protected $table = 'result_sheet';

    protected $fillable = [
        'student_id',
        'exam_id',
        'total_mark_obtains',
        'total_marks',
        'percentage',
        'grade',
        'position',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subjectMarks()
    {
        return $this->hasMany(SubjectMarkSheet::class, 'student_id', 'student_id')
            ->where('exam_id', $this->exam_id);
    }

    public static function calculateGrade($percentage)
    {
        if ($percentage >= 80 && $percentage <= 100) {
            return 'A+';
        } elseif ($percentage >= 70 && $percentage <= 79) {
            return 'A';
        } elseif ($percentage >= 60 && $percentage <= 69) {
            return 'B';
        } elseif ($percentage >= 50 && $percentage <= 59) {
            return 'C';
        } elseif ($percentage >= 40 && $percentage <= 49) {
            return 'D';
        } elseif ($percentage >= 33 && $percentage <= 39) {
            return 'E';
        } else {
            return 'Fail';
        }
    }
} 