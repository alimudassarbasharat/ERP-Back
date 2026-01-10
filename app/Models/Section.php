<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScope;

class Section extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'name',
        'description',
        'status',
        'class_id',
        'created_by',
        'updated_by',
        'merchant_id'
    ];

    /**
     * Get the class that this section belongs to (one-to-many).
     * Legacy relationship if class_id exists in sections table.
     */
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id', 'id');
    }

    /**
     * Get the classes that this section belongs to (many-to-many).
     * Uses pivot table class_section.
     */
    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_section', 'section_id', 'class_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * Get the user who created the section.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the section.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
} 