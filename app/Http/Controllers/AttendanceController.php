<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display attendance dashboard
     */
    public function index()
    {
        $stats = $this->getAttendanceStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats()
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $todayPresent = AttendanceRecord::forDate($today)
            ->where('status', AttendanceRecord::STATUS_PRESENT)
            ->count();

        $todayAbsent = AttendanceRecord::forDate($today)
            ->where('status', AttendanceRecord::STATUS_ABSENT)
            ->count();

        $todayTotal = Student::where('status', 'active')->count();

        $monthlyStats = AttendanceRecord::forMonth($currentMonth, $currentYear)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'today' => [
                'present' => $todayPresent,
                'absent' => $todayAbsent,
                'total' => $todayTotal
            ],
            'monthly' => $monthlyStats
        ];
    }

    /**
     * Get students for attendance marking
     */
    public function getStudentsForAttendance(\App\Http\Requests\Attendance\GetStudentsForAttendanceRequest $request)
    {
        // Validated by Form Request

        $students = Student::with(['class', 'section'])
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->get();

        // Check if attendance already exists
        $existingAttendance = AttendanceRecord::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('attendance_date', $request->date)
            ->where('attendance_mode', $request->attendance_mode)
            ->when($request->attendance_mode === 'period_wise', function ($query) use ($request) {
                return $query->where('subject_id', $request->subject_id)
                           ->where('period_number', $request->period_number);
            })
            ->get()
            ->keyBy('student_id');

        $studentsWithAttendance = $students->map(function ($student) use ($existingAttendance) {
            $attendance = $existingAttendance->get($student->id);
            
            return [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'roll_number' => $student->roll_number,
                'class' => $student->class->name ?? '',
                'section' => $student->section->name ?? '',
                'status' => $attendance ? $attendance->status : AttendanceRecord::STATUS_PRESENT,
                'time_in' => $attendance ? $attendance->time_in?->format('H:i') : null,
                'time_out' => $attendance ? $attendance->time_out?->format('H:i') : null,
                'remarks' => $attendance ? $attendance->remarks : '',
                'is_marked' => $attendance ? true : false
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $studentsWithAttendance
        ]);
    }

    /**
     * Mark attendance (Daily Mode)
     */
    public function markDailyAttendance(\App\Http\Requests\Attendance\MarkDailyAttendanceRequest $request)
    {
        // Validated by Form Request

        $academicYear = AcademicYear::current()->first();
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $attendance) {
                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $attendance['student_id'],
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'attendance_date' => $request->date,
                        'attendance_mode' => 'daily',
                        'academic_year_id' => $academicYear->id
                    ],
                    [
                        'status' => $attendance['status'],
                        'time_in' => $attendance['time_in'] ? Carbon::createFromFormat('H:i', $attendance['time_in']) : null,
                        'time_out' => $attendance['time_out'] ? Carbon::createFromFormat('H:i', $attendance['time_out']) : null,
                        'remarks' => $attendance['remarks'] ?? '',
                        'marked_by' => Auth::id(),
                        'marked_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark attendance (Period-wise Mode)
     */
    public function markPeriodAttendance(\App\Http\Requests\Attendance\MarkPeriodAttendanceRequest $request)
    {
        // Validated by Form Request

        $academicYear = AcademicYear::current()->first();
        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $attendance) {
                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $attendance['student_id'],
                        'class_id' => $request->class_id,
                        'section_id' => $request->section_id,
                        'subject_id' => $request->subject_id,
                        'attendance_date' => $request->date,
                        'attendance_mode' => 'period_wise',
                        'period_number' => $request->period_number,
                        'academic_year_id' => $academicYear->id
                    ],
                    [
                        'teacher_id' => $request->teacher_id,
                        'status' => $attendance['status'],
                        'time_in' => $attendance['time_in'] ? Carbon::createFromFormat('H:i', $attendance['time_in']) : null,
                        'time_out' => $attendance['time_out'] ? Carbon::createFromFormat('H:i', $attendance['time_out']) : null,
                        'remarks' => $attendance['remarks'] ?? '',
                        'marked_by' => Auth::id(),
                        'marked_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Period attendance marked successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark period attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance records with filters
     */
    public function getAttendanceRecords(Request $request)
    {
        $query = AttendanceRecord::with(['student', 'class', 'section', 'subject', 'teacher'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->date) {
            $query->whereDate('attendance_date', $request->date);
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('attendance_date', [$request->date_from, $request->date_to]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->attendance_mode) {
            $query->where('attendance_mode', $request->attendance_mode);
        }

        $records = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    /**
     * Get attendance summary for a student
     */
    public function getStudentAttendanceSummary(\App\Http\Requests\Attendance\GetAttendanceSummaryRequest $request, $studentId)
    {
        // Validated by Form Request

        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $query = AttendanceRecord::where('student_id', $studentId)
            ->forMonth($month, $year);

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $records = $query->get();

        $summary = [
            'total_days' => $records->count(),
            'present' => $records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
            'absent' => $records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'late' => $records->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'leave' => $records->where('status', AttendanceRecord::STATUS_LEAVE)->count(),
            'medical' => $records->where('status', AttendanceRecord::STATUS_MEDICAL)->count(),
            'online_present' => $records->where('status', AttendanceRecord::STATUS_ONLINE_PRESENT)->count(),
            'proxy_suspected' => $records->where('status', AttendanceRecord::STATUS_PROXY_SUSPECTED)->count(),
        ];

        $summary['attendance_percentage'] = $summary['total_days'] > 0 
            ? round(($summary['present'] + $summary['late'] + $summary['online_present']) / $summary['total_days'] * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get defaulters list (students with attendance below threshold)
     */
    public function getDefaultersList(\App\Http\Requests\Attendance\GetAttendanceSummaryRequest $request)
    {
        // Validated by Form Request (shared rules + threshold handled below)

        $threshold = $request->threshold ?? 75;
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $query = Student::with(['class', 'section'])
            ->where('status', 'active');

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        $students = $query->get();

        $defaulters = $students->map(function ($student) use ($month, $year, $request, $threshold) {
            $attendanceQuery = AttendanceRecord::where('student_id', $student->id)
                ->forMonth($month, $year);

            if ($request->subject_id) {
                $attendanceQuery->where('subject_id', $request->subject_id);
            }

            $records = $attendanceQuery->get();
            $totalDays = $records->count();
            $presentDays = $records->whereIn('status', [
                AttendanceRecord::STATUS_PRESENT,
                AttendanceRecord::STATUS_LATE,
                AttendanceRecord::STATUS_ONLINE_PRESENT
            ])->count();

            $percentage = $totalDays > 0 ? round($presentDays / $totalDays * 100, 2) : 0;

            return [
                'student' => $student,
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'attendance_percentage' => $percentage,
                'is_defaulter' => $percentage < $threshold
            ];
        })->filter(function ($item) {
            return $item['is_defaulter'];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $defaulters
        ]);
    }

    /**
     * Update attendance record
     */
    public function updateAttendance(\App\Http\Requests\Attendance\UpdateAttendanceRequest $request, $id)
    {
        // Validated by Form Request

        $attendance = AttendanceRecord::findOrFail($id);

        $attendance->update([
            'status' => $request->status,
            'time_in' => $request->time_in ? Carbon::createFromFormat('H:i', $request->time_in) : null,
            'time_out' => $request->time_out ? Carbon::createFromFormat('H:i', $request->time_out) : null,
            'remarks' => $request->remarks ?? '',
            'marked_by' => Auth::id(),
            'marked_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => $attendance->load(['student', 'class', 'section', 'subject', 'teacher'])
        ]);
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance($id)
    {
        $attendance = AttendanceRecord::findOrFail($id);
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    }

    /**
     * Get attendance status options
     */
    public function getStatusOptions()
    {
        return response()->json([
            'success' => true,
            'data' => AttendanceRecord::STATUS_OPTIONS
        ]);
    }

    /**
     * Get attendance modes
     */
    public function getAttendanceModes()
    {
        return response()->json([
            'success' => true,
            'data' => AttendanceRecord::ATTENDANCE_MODES
        ]);
    }

    /**
     * Get subjects for a class
     */
    public function getSubjectsForClass($classId)
    {
        $subjects = Subject::whereHas('classes', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        })->active()->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Get students with pagination for attendance (NEW ENDPOINT)
     */
    public function getStudentsWithPagination(\App\Http\Requests\Attendance\GetStudentsPaginatedRequest $request)
    {
        // Validated by Form Request

        $query = Student::with(['class', 'section'])
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active');

        // Add session filter if provided
        if ($request->session_id) {
            $query->whereHas('class', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        // Add subject filter for subject-wise attendance
        if ($request->subject_id && $request->attendance_mode === 'period_wise') {
            $query->whereHas('class.subjects', function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
        }

        // Add search functionality
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->per_page ?? 10;
        $students = $query->orderBy('roll_number')->paginate($perPage);

        // Get attendance data for the selected date
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        $attendanceData = [];

        if ($request->class_id && $request->section_id) {
            $attendanceQuery = AttendanceRecord::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('attendance_date', $date);

            if ($request->subject_id && $request->attendance_mode === 'period_wise') {
                $attendanceQuery->where('subject_id', $request->subject_id);
            }

            $attendanceRecords = $attendanceQuery->get()->keyBy('student_id');

            // Calculate attendance statistics for each student
            foreach ($students->items() as $student) {
                $studentAttendance = AttendanceRecord::where('student_id', $student->id)
                    ->when($request->subject_id, function ($q) use ($request) {
                        return $q->where('subject_id', $request->subject_id);
                    })
                    ->get();

                $totalClasses = $studentAttendance->count();
                $presentClasses = $studentAttendance->whereIn('status', [
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_LATE,
                    AttendanceRecord::STATUS_ONLINE_PRESENT
                ])->count();

                $attendancePercentage = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 1) : 0;

                $currentAttendance = $attendanceRecords->get($student->id);

                $student->attendance_status = $currentAttendance ? $currentAttendance->status : 'not_marked';
                $student->attendance_percentage = $attendancePercentage;
                $student->total_classes = $totalClasses;
                $student->present_classes = $presentClasses;
                $student->time_in = $currentAttendance ? $currentAttendance->time_in?->format('H:i') : null;
                $student->time_out = $currentAttendance ? $currentAttendance->time_out?->format('H:i') : null;
                $student->remarks = $currentAttendance ? $currentAttendance->remarks : '';
                $student->is_marked = $currentAttendance ? true : false;
            }
        }

        return response()->json([
            'success' => true,
            'result' => $students
        ]);
    }

    /**
     * Get teachers for a subject
     */
    public function getTeachersForSubject($subjectId)
    {
        $teachers = Teacher::whereHas('subjects', function ($query) use ($subjectId) {
            $query->where('subject_id', $subjectId);
        })->active()->get();

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }
} 