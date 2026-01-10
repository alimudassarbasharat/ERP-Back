<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScope;

class Classes extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $table = 'classes';
    
    protected $fillable = [
        'name',
        'description',
        'section_id',
        'merchant_id'
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id', 'id');
    }

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withTimestamps();
    }

    /**
     * Get the sections that belong to this class (many-to-many).
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'class_section', 'class_id', 'section_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * Legacy: Get single section (if section_id exists in classes table for backward compatibility)
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
}
