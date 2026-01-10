<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScope;

class ExamQuestion extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'merchant_id',
        'exam_paper_id',
        'section_name',
        'question_text',
        'question_type',
        'marks',
        'options_json',
        'answer_key',
        'order_no',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'options_json' => 'array',
    ];

    /**
     * Get the exam paper
     */
    public function examPaper()
    {
        return $this->belongsTo(ExamPaper::class);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('section_name')->orderBy('order_no');
    }

    /**
     * Scope for section
     */
    public function scopeForSection($query, string $section)
    {
        return $query->where('section_name', $section);
    }
}
