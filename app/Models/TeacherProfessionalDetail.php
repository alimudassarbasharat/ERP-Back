<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherProfessionalDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'qualification',
        'specialization',
        'years_of_experience',
        'joining_date',
        'designation',
        'department_id',
        'salary',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'years_of_experience' => 'integer',
        'salary' => 'decimal:2'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
