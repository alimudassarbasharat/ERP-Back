<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_group_id',
        'student_id',
    ];

    /**
     * Get the family group
     */
    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
