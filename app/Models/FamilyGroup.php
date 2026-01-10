<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'family_code',
    ];

    /**
     * Get the school that owns this family group
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get all students in this family group
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'family_students', 'family_group_id', 'student_id')
            ->withTimestamps();
    }

    /**
     * Get family_students pivot records
     */
    public function familyStudents()
    {
        return $this->hasMany(FamilyStudent::class);
    }
}
