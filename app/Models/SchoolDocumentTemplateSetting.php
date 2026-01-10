<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolDocumentTemplateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'type',
        'template_id',
        'config_json',
        'is_default',
    ];

    protected $casts = [
        'config_json' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the template
     */
    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Get default template for school and type
     */
    public static function getDefault(int $schoolId, string $type): ?self
    {
        return self::where('school_id', $schoolId)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();
    }
}
