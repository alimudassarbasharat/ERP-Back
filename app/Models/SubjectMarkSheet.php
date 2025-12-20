<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectMarkSheet extends Model
{
    protected $guarded = [];
    protected $table = 'subject_mark_sheets';

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
} 