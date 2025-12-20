<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceReportsController extends Controller
{
    /**
     * Get daily attendance sheet
     */
    public function getDailyAttendanceSheet(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'subject_id' => 'nullable|exists:subjects,id'
        ]);

        $query = AttendanceRecord::with(['student', 'subject', 'teacher'])
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereDate('attendance_date', $request->date);

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $records = $query->get();

        // Group by student
        $studentRecords = $records->groupBy('student_id');

        $attendanceSheet = [];
        foreach ($studentRecords as $studentId => $studentAttendance) {
            $student = $studentAttendance->first()->student;
            $attendanceSheet[] = [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'roll_number' => $student->roll_number
                ],
                'attendance' => $studentAttendance->map(function ($record) {
                    return [
                        'subject' => $record->subject ? $record->subject->name : 'General',
                        'period' => $record->period_number,
                        'status' => $record->status,
                        'time_in' => $record->time_in?->format('H:i'),
                        'time_out' => $record->time_out?->format('H:i'),
                        'teacher' => $record->teacher ? $record->teacher->full_name : null,
                        'remarks' => $record->remarks
                    ];
                })
            ];
        }

        // Summary statistics
        $summary = [
            'total_students' => $studentRecords->count(),
            'present' => $records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
            'absent' => $records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'late' => $records->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'leave' => $records->where('status', AttendanceRecord::STATUS_LEAVE)->count(),
            'medical' => $records->where('status', AttendanceRecord::STATUS_MEDICAL)->count(),
            'online_present' => $records->where('status', AttendanceRecord::STATUS_ONLINE_PRESENT)->count(),
            'proxy_suspected' => $records->where('status', AttendanceRecord::STATUS_PROXY_SUSPECTED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'attendance_sheet' => $attendanceSheet,
                'summary' => $summary,
                'date' => $request->date,
                'class' => Classes::find($request->class_id),
                'section' => Section::find($request->section_id),
                'subject' => $request->subject_id ? Subject::find($request->subject_id) : null
            ]
        ]);
    }

    /**
     * Get monthly attendance summary
     */
    public function getMonthlyAttendanceSummary(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030'
        ]);

        $query = AttendanceRecord::with(['student', 'class', 'section', 'subject'])
            ->whereMonth('attendance_date', $request->month)
            ->whereYear('attendance_date', $request->year);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $records = $query->get();

        // Group by student
        $studentSummaries = $records->groupBy('student_id')->map(function ($studentRecords, $studentId) {
            $student = $studentRecords->first()->student;
            $totalDays = $studentRecords->count();
            
            $statusCounts = [
                'present' => $studentRecords->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
                'absent' => $studentRecords->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
                'late' => $studentRecords->where('status', AttendanceRecord::STATUS_LATE)->count(),
                'leave' => $studentRecords->where('status', AttendanceRecord::STATUS_LEAVE)->count(),
                'medical' => $studentRecords->where('status', AttendanceRecord::STATUS_MEDICAL)->count(),
                'online_present' => $studentRecords->where('status', AttendanceRecord::STATUS_ONLINE_PRESENT)->count(),
                'proxy_suspected' => $studentRecords->where('status', AttendanceRecord::STATUS_PROXY_SUSPECTED)->count(),
            ];

            $presentDays = $statusCounts['present'] + $statusCounts['late'] + $statusCounts['online_present'];
            $attendancePercentage = $totalDays > 0 ? round($presentDays / $totalDays * 100, 2) : 0;

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'roll_number' => $student->roll_number,
                    'class' => $student->class->name ?? '',
                    'section' => $student->section->name ?? ''
                ],
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'attendance_percentage' => $attendancePercentage,
                'status_breakdown' => $statusCounts,
                'is_defaulter' => $attendancePercentage < 75
            ];
        })->values();

        // Overall statistics
        $overallStats = [
            'total_students' => $studentSummaries->count(),
            'average_attendance' => $studentSummaries->avg('attendance_percentage'),
            'defaulters_count' => $studentSummaries->where('is_defaulter', true)->count(),
            'excellent_attendance' => $studentSummaries->where('attendance_percentage', '>=', 90)->count(),
            'good_attendance' => $studentSummaries->whereBetween('attendance_percentage', [75, 89])->count(),
            'poor_attendance' => $studentSummaries->where('attendance_percentage', '<', 75)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'student_summaries' => $studentSummaries,
                'overall_stats' => $overallStats,
                'month' => $request->month,
                'year' => $request->year,
                'class' => $request->class_id ? Classes::find($request->class_id) : null,
                'section' => $request->section_id ? Section::find($request->section_id) : null,
                'subject' => $request->subject_id ? Subject::find($request->subject_id) : null
            ]
        ]);
    }

    /**
     * Get subject-wise attendance report
     */
    public function getSubjectWiseReport(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030'
        ]);

        $subjects = Subject::whereHas('classes', function ($query) use ($request) {
            $query->where('class_id', $request->class_id);
        })->get();

        $subjectReports = [];

        foreach ($subjects as $subject) {
            $records = AttendanceRecord::with(['student'])
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('subject_id', $subject->id)
                ->whereMonth('attendance_date', $request->month)
                ->whereYear('attendance_date', $request->year)
                ->get();

            $studentSummaries = $records->groupBy('student_id')->map(function ($studentRecords, $studentId) {
                $student = $studentRecords->first()->student;
                $totalClasses = $studentRecords->count();
                $presentClasses = $studentRecords->whereIn('status', [
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_LATE,
                    AttendanceRecord::STATUS_ONLINE_PRESENT
                ])->count();

                $percentage = $totalClasses > 0 ? round($presentClasses / $totalClasses * 100, 2) : 0;

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'roll_number' => $student->roll_number,
                    'total_classes' => $totalClasses,
                    'present_classes' => $presentClasses,
                    'attendance_percentage' => $percentage
                ];
            })->values();

            $subjectStats = [
                'total_students' => $studentSummaries->count(),
                'average_attendance' => $studentSummaries->avg('attendance_percentage'),
                'total_classes_conducted' => $records->groupBy('attendance_date')->count(),
                'highest_attendance' => $studentSummaries->max('attendance_percentage'),
                'lowest_attendance' => $studentSummaries->min('attendance_percentage')
            ];

            $subjectReports[] = [
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code
                ],
                'student_summaries' => $studentSummaries,
                'subject_stats' => $subjectStats
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subject_reports' => $subjectReports,
                'month' => $request->month,
                'year' => $request->year,
                'class' => Classes::find($request->class_id),
                'section' => Section::find($request->section_id)
            ]
        ]);
    }

    /**
     * Get attendance trends and analytics
     */
    public function getAttendanceTrends(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from'
        ]);

        $query = AttendanceRecord::whereBetween('attendance_date', [$request->date_from, $request->date_to]);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        // Daily trends
        $dailyTrends = $query->clone()
            ->select(
                DB::raw('DATE(attendance_date) as date'),
                DB::raw('COUNT(*) as total_records'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count')
            )
            ->groupBy(DB::raw('DATE(attendance_date)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->attendance_percentage = $item->total_records > 0 
                    ? round($item->present_count / $item->total_records * 100, 2) 
                    : 0;
                return $item;
            });

        // Weekly trends
        $weeklyTrends = $query->clone()
            ->select(
                DB::raw('YEARWEEK(attendance_date) as week'),
                DB::raw('COUNT(*) as total_records'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                DB::raw('AVG(CASE WHEN status = "present" THEN 1 ELSE 0 END) * 100 as avg_attendance_percentage')
            )
            ->groupBy(DB::raw('YEARWEEK(attendance_date)'))
            ->orderBy('week')
            ->get();

        // Status distribution
        $statusDistribution = $query->clone()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Top performers and defaulters
        $studentPerformance = $query->clone()
            ->with('student')
            ->get()
            ->groupBy('student_id')
            ->map(function ($records, $studentId) {
                $student = $records->first()->student;
                $totalRecords = $records->count();
                $presentRecords = $records->whereIn('status', [
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_LATE,
                    AttendanceRecord::STATUS_ONLINE_PRESENT
                ])->count();

                $percentage = $totalRecords > 0 ? round($presentRecords / $totalRecords * 100, 2) : 0;

                return [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'roll_number' => $student->roll_number,
                    'attendance_percentage' => $percentage,
                    'total_days' => $totalRecords,
                    'present_days' => $presentRecords
                ];
            })
            ->sortByDesc('attendance_percentage')
            ->values();

        $topPerformers = $studentPerformance->take(10);
        $defaulters = $studentPerformance->where('attendance_percentage', '<', 75)->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'daily_trends' => $dailyTrends,
                'weekly_trends' => $weeklyTrends,
                'status_distribution' => $statusDistribution,
                'top_performers' => $topPerformers,
                'defaulters' => $defaulters,
                'date_range' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to
                ]
            ]
        ]);
    }

    /**
     * Get class comparison report
     */
    public function getClassComparison(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'subject_id' => 'nullable|exists:subjects,id'
        ]);

        $query = AttendanceRecord::with(['class', 'section'])
            ->whereMonth('attendance_date', $request->month)
            ->whereYear('attendance_date', $request->year);

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $classComparison = $query->get()
            ->groupBy(['class_id', 'section_id'])
            ->map(function ($sectionRecords, $classId) {
                return $sectionRecords->map(function ($records, $sectionId) {
                    $class = $records->first()->class;
                    $section = $records->first()->section;
                    
                    $totalRecords = $records->count();
                    $presentRecords = $records->whereIn('status', [
                        AttendanceRecord::STATUS_PRESENT,
                        AttendanceRecord::STATUS_LATE,
                        AttendanceRecord::STATUS_ONLINE_PRESENT
                    ])->count();

                    $percentage = $totalRecords > 0 ? round($presentRecords / $totalRecords * 100, 2) : 0;

                    return [
                        'class_name' => $class->name,
                        'section_name' => $section->name,
                        'total_records' => $totalRecords,
                        'present_records' => $presentRecords,
                        'attendance_percentage' => $percentage,
                        'unique_students' => $records->groupBy('student_id')->count()
                    ];
                });
            })
            ->flatten(1)
            ->sortByDesc('attendance_percentage')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'class_comparison' => $classComparison,
                'month' => $request->month,
                'year' => $request->year,
                'subject' => $request->subject_id ? Subject::find($request->subject_id) : null
            ]
        ]);
    }

    /**
     * Export attendance report
     */
    public function exportReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:daily,monthly,subject_wise,trends,class_comparison',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        // This would typically generate and return a file
        // For now, we'll return the data that would be exported
        
        switch ($request->report_type) {
            case 'daily':
                $data = $this->getDailyAttendanceSheet($request);
                break;
            case 'monthly':
                $data = $this->getMonthlyAttendanceSummary($request);
                break;
            case 'subject_wise':
                $data = $this->getSubjectWiseReport($request);
                break;
            case 'trends':
                $data = $this->getAttendanceTrends($request);
                break;
            case 'class_comparison':
                $data = $this->getClassComparison($request);
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid report type'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report data prepared for export',
            'data' => $data->getData()->data,
            'export_info' => [
                'type' => $request->report_type,
                'format' => $request->format,
                'generated_at' => now()->toDateTimeString()
            ]
        ]);
    }
} 