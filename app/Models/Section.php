<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the classes associated with the section.
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
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