<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherPersonalDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'gender',
        'date_of_birth',
        'cnic',
        'religion',
        'blood_group',
        'profile_picture'
    ];

    protected $casts = [
        'date_of_birth' => 'date'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
} 