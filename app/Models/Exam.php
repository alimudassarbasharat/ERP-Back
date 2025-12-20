<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'term',
        'academic_year',
        'merchant_id',
        'created_by',
    ];

    /**
     * The subjects that belong to the exam.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
}
