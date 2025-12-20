<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentForm extends Model
{
    use HasFactory;

    protected $gaurded = ['*'];

    protected $casts = [
        'marks' => 'array',
        'observations' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
} 