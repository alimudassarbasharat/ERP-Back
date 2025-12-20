<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'attendance_date',
        'attendance_mode', // 'daily' or 'period_wise'
        'period_number',
        'status', // 'present', 'absent', 'late', 'leave', 'medical', 'online_present', 'proxy_suspected'
        'time_in',
        'time_out',
        'marked_by',
        'marked_at',
        'remarks',
        'is_regularized',
        'regularization_reason',
        'regularization_approved_by',
        'regularization_approved_at',
        'parent_notified',
        'notification_sent_at'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'marked_at' => 'datetime',
        'regularization_approved_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'is_regularized' => 'boolean',
        'parent_notified' => 'boolean'
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function regularizationApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regularization_approved_by');
    }

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('attendance_mode', 'daily');
    }

    public function scopePeriodWise($query)
    {
        return $query->where('attendance_mode', 'period_wise');
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('attendance_date', $month)
                    ->whereYear('attendance_date', $year);
    }

    // Status Constants
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_LEAVE = 'leave';
    const STATUS_MEDICAL = 'medical';
    const STATUS_ONLINE_PRESENT = 'online_present';
    const STATUS_PROXY_SUSPECTED = 'proxy_suspected';

    const ATTENDANCE_MODES = [
        'daily' => 'Daily',
        'period_wise' => 'Period-wise'
    ];

    const STATUS_OPTIONS = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_ABSENT => 'Absent',
        self::STATUS_LATE => 'Late',
        self::STATUS_LEAVE => 'Leave (Approved)',
        self::STATUS_MEDICAL => 'Medical Leave',
        self::STATUS_ONLINE_PRESENT => 'Online Present',
        self::STATUS_PROXY_SUSPECTED => 'Proxy Suspected'
    ];

    // Helper Methods
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PRESENT => 'green',
            self::STATUS_ABSENT => 'red',
            self::STATUS_LATE => 'yellow',
            self::STATUS_LEAVE => 'blue',
            self::STATUS_MEDICAL => 'purple',
            self::STATUS_ONLINE_PRESENT => 'cyan',
            self::STATUS_PROXY_SUSPECTED => 'orange',
            default => 'gray'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            self::STATUS_PRESENT => '✅',
            self::STATUS_ABSENT => '❌',
            self::STATUS_LATE => '⏰',
            self::STATUS_LEAVE => '📝',
            self::STATUS_MEDICAL => '💊',
            self::STATUS_ONLINE_PRESENT => '🌐',
            self::STATUS_PROXY_SUSPECTED => '⚠️',
            default => '❓'
        };
    }
} 