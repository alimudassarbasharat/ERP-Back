<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAdditionalDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'bank_account_details',
        'remarks',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
} 