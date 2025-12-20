<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';
    
    protected $guarded = [];

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id', 'id');
    }

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Subject::class, 'class_subjects', 'class_id', 'subject_id');
    }

    // public function session()
    // {
    //     return $this->belongsTo(Session::class, 'class_id', 'id');
    // }
}
