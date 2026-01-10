<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'key',
        'name',
        'blade_view',
        'preview_image',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all school settings using this template
     */
    public function schoolSettings()
    {
        return $this->hasMany(SchoolDocumentTemplateSetting::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
