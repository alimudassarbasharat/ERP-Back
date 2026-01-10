<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory, SoftDeletes, BelongsToMerchant;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'school_id',
        'notes',
        'created_by',
        'updated_by',
        'merchant_id'
    ];

    protected $casts = [
        'status' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Get the students associated with the session.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the classes associated with the session.
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /**
     * Get the user who created the session.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the session.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the school
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get fee structures for this session
     */
    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * Get fee invoices for this session
     */
    public function feeInvoices()
    {
        return $this->hasMany(FeeInvoice::class);
    }

    /**
     * Get exams for this session
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Check if session dates are valid
     */
    public function hasValidDates(): bool
    {
        return $this->start_date && $this->end_date && $this->start_date <= $this->end_date;
    }

    /**
     * Check if session overlaps with another session for the same school
     */
    public function hasOverlap(): bool
    {
        if (!$this->hasValidDates() || !$this->school_id) {
            return false;
        }

        return self::where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($innerQuery) {
                        $innerQuery->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            })
            ->exists();
    }

    /**
     * Activate this session (deactivates others for same school)
     */
    public function activate(): bool
    {
        if (!$this->hasValidDates()) {
            return false;
        }

        \DB::transaction(function () {
            // Deactivate other sessions for this school
            self::where('school_id', $this->school_id)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false, 'status' => 'archived']);

            // Activate this session
            $this->update(['is_active' => true, 'status' => 'active']);
        });

        return true;
    }

    /**
     * Archive this session
     */
    public function archive(): bool
    {
        return $this->update(['is_active' => false, 'status' => 'archived']);
    }

    /**
     * Check if session can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->students()->count() === 0 && 
               $this->classes()->count() === 0 && 
               $this->feeInvoices()->count() === 0 && 
               $this->exams()->count() === 0;
    }

    /**
     * Get session duration in days
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->hasValidDates()) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if session is current (today falls within session dates)
     */
    public function isCurrent(): bool
    {
        if (!$this->hasValidDates()) {
            return false;
        }

        $today = now()->toDateString();
        return $today >= $this->start_date->toDateString() && 
               $today <= $this->end_date->toDateString();
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sessions by school
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}