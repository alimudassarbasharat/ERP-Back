<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceReportController extends Controller
{
    /**
     * Get available attendance report templates.
     */
    public function getTemplates()
    {
        $templates = [
            [
                'id' => 'chart-analytics',
                'name' => 'Chart Analytics',
                'description' => 'Visual charts and graphs for attendance data',
                'premium' => true,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'chart-analytics'])
            ],
            [
                'id' => 'calendar-grid',
                'name' => 'Calendar Grid',
                'description' => 'Monthly calendar view with attendance markers',
                'premium' => false,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'calendar-grid'])
            ],
            [
                'id' => 'dashboard-style',
                'name' => 'Dashboard Style',
                'description' => 'Modern dashboard layout with key metrics',
                'premium' => true,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'dashboard-style'])
            ],
            [
                'id' => 'simple-table',
                'name' => 'Simple Table',
                'description' => 'Clean table format with attendance records',
                'premium' => false,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'simple-table'])
            ],
            [
                'id' => 'summary-report',
                'name' => 'Summary Report',
                'description' => 'Comprehensive summary with statistics',
                'premium' => false,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'summary-report'])
            ],
            [
                'id' => 'timeline-view',
                'name' => 'Timeline View',
                'description' => 'Timeline based attendance visualization',
                'premium' => true,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'timeline-view'])
            ],
            [
                'id' => 'grade-book',
                'name' => 'Grade Book Style',
                'description' => 'Traditional grade book layout design',
                'premium' => false,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'grade-book'])
            ],
            [
                'id' => 'modern-infographic',
                'name' => 'Modern Infographic',
                'description' => 'Infographic style with visual elements',
                'premium' => true,
                'preview_url' => route('api.attendance-report.preview', ['template' => 'modern-infographic'])
            ]
        ];

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Preview an attendance report template.
     */
    public function previewTemplate(Request $request)
    {
        $template = $request->input('template', 'chart-analytics');
        
        // Create sample student data for preview
        $sampleStudent = (object)[
            'id' => 1,
            'first_name' => 'Sara',
            'last_name' => 'Khan',
            'roll_number' => 'STD-2024-003',
            'class' => (object)['name' => 'Class IX'],
            'section' => 'A'
        ];

        // Generate sample attendance data
        $attendanceData = [];
        $totalDays = 30;
        $presentDays = 0;
        
        for ($i = 1; $i <= $totalDays; $i++) {
            $status = rand(1, 10) > 2 ? 'Present' : 'Absent'; // 80% attendance
            if ($status === 'Present') $presentDays++;
            
            $attendanceData[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'status' => $status,
                'remarks' => $status === 'Absent' ? 'Family function' : ''
            ];
        }

        $attendanceSummary = [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $totalDays - $presentDays,
            'attendance_percentage' => round(($presentDays / $totalDays) * 100, 2),
            'month' => date('F Y'),
            'generated_on' => date('d M, Y')
        ];

        try {
            $pdf = PDF::loadView("attendance-reports.{$template}", [
                'student' => $sampleStudent,
                'attendanceData' => $attendanceData,
                'attendanceSummary' => $attendanceSummary
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('attendance-report-preview.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="attendance-report-preview.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback to default template
            $pdf = PDF::loadView("attendance-reports.chart-analytics", [
                'student' => $sampleStudent,
                'attendanceData' => $attendanceData,
                'attendanceSummary' => $attendanceSummary
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('attendance-report-preview.pdf');
        }
    }

    /**
     * Generate attendance report for a student.
     */
    public function generate(Request $request, Student $student)
    {
        $template = $request->input('template', 'chart-analytics');
        $period = $request->input('period', 'monthly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Here you would fetch actual attendance data from database
        // For now, using sample data
        $attendanceData = [];
        $totalDays = 30;
        $presentDays = 24;
        
        for ($i = 1; $i <= $totalDays; $i++) {
            $attendanceData[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'status' => rand(1, 10) > 2 ? 'Present' : 'Absent',
                'remarks' => ''
            ];
        }

        $attendanceSummary = [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $totalDays - $presentDays,
            'attendance_percentage' => round(($presentDays / $totalDays) * 100, 2),
            'month' => date('F Y'),
            'generated_on' => date('d M, Y')
        ];
        
        try {
            $pdf = PDF::loadView("attendance-reports.{$template}", [
                'student' => $student,
                'attendanceData' => $attendanceData,
                'attendanceSummary' => $attendanceSummary
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            $filename = "attendance-report-{$student->roll_number}.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 