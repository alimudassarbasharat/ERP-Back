<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProgressReportController extends Controller
{
    /**
     * Get available progress report templates.
     */
    public function getTemplates()
    {
        $templates = [
            [
                'id' => 'premium-detailed',
                'name' => 'Premium Detailed',
                'description' => 'Comprehensive report with detailed analytics',
                'premium' => true,
                'preview_url' => route('api.progress-report.preview', ['template' => 'premium-detailed'])
            ],
            [
                'id' => 'modern-scorecard',
                'name' => 'Modern Scorecard',
                'description' => 'Modern scorecard design with visual elements',
                'premium' => false,
                'preview_url' => route('api.progress-report.preview', ['template' => 'modern-scorecard'])
            ],
            [
                'id' => 'classic-report',
                'name' => 'Classic Report',
                'description' => 'Traditional academic report card format',
                'premium' => false,
                'preview_url' => route('api.progress-report.preview', ['template' => 'classic-report'])
            ],
            [
                'id' => 'graphical-analysis',
                'name' => 'Graphical Analysis',
                'description' => 'Charts and graphs for performance visualization',
                'premium' => true,
                'preview_url' => route('api.progress-report.preview', ['template' => 'graphical-analysis'])
            ],
            [
                'id' => 'subject-breakdown',
                'name' => 'Subject Breakdown',
                'description' => 'Detailed subject-wise performance analysis',
                'premium' => false,
                'preview_url' => route('api.progress-report.preview', ['template' => 'subject-breakdown'])
            ],
            [
                'id' => 'comparative-report',
                'name' => 'Comparative Report',
                'description' => 'Compare performance with class average',
                'premium' => true,
                'preview_url' => route('api.progress-report.preview', ['template' => 'comparative-report'])
            ],
            [
                'id' => 'summary-card',
                'name' => 'Summary Card',
                'description' => 'Concise summary with key highlights',
                'premium' => false,
                'preview_url' => route('api.progress-report.preview', ['template' => 'summary-card'])
            ],
            [
                'id' => 'elite-transcript',
                'name' => 'Elite Transcript',
                'description' => 'Professional transcript with luxury design',
                'premium' => true,
                'preview_url' => route('api.progress-report.preview', ['template' => 'elite-transcript'])
            ]
        ];

        return response()->json([
            'success' => true,
            'result' => $templates
        ]);
    }

    /**
     * Preview a progress report template.
     */
    public function previewTemplate(Request $request)
    {
        $template = $request->input('template', 'premium-detailed');
        
        // Create sample student data for preview
        $sampleStudent = (object)[
            'id' => 1,
            'first_name' => 'Hassan',
            'last_name' => 'Ali',
            'roll_number' => 'STD-2024-004',
            'class' => (object)['name' => 'Class XI'],
            'section' => 'A',
            'father_name' => 'Muhammad Ali'
        ];

        // Sample grades and subjects
        $subjects = [
            ['name' => 'Mathematics', 'obtained_marks' => 85, 'total_marks' => 100, 'grade' => 'A'],
            ['name' => 'Physics', 'obtained_marks' => 78, 'total_marks' => 100, 'grade' => 'B+'],
            ['name' => 'Chemistry', 'obtained_marks' => 82, 'total_marks' => 100, 'grade' => 'A-'],
            ['name' => 'English', 'obtained_marks' => 88, 'total_marks' => 100, 'grade' => 'A'],
            ['name' => 'Urdu', 'obtained_marks' => 75, 'total_marks' => 100, 'grade' => 'B+'],
            ['name' => 'Biology', 'obtained_marks' => 80, 'total_marks' => 100, 'grade' => 'A-'],
            ['name' => 'Computer Science', 'obtained_marks' => 92, 'total_marks' => 100, 'grade' => 'A+']
        ];

        $reportData = [
            'subjects' => $subjects,
            'total_obtained' => array_sum(array_column($subjects, 'obtained_marks')),
            'total_marks' => array_sum(array_column($subjects, 'total_marks')),
            'percentage' => round((array_sum(array_column($subjects, 'obtained_marks')) / array_sum(array_column($subjects, 'total_marks'))) * 100, 2),
            'overall_grade' => 'A',
            'position' => 3,
            'total_students' => 45,
            'exam_term' => 'First Term',
            'academic_year' => '2024-25',
            'attendance_percentage' => 92.5,
            'remarks' => 'Excellent performance. Keep up the good work!',
            'generated_on' => date('d M, Y')
        ];

        try {
            $pdf = PDF::loadView("progress-reports.{$template}", [
                'student' => $sampleStudent,
                'reportData' => $reportData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('progress-report-preview.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="progress-report-preview.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback to default template
            $pdf = PDF::loadView("progress-reports.premium-detailed", [
                'student' => $sampleStudent,
                'reportData' => $reportData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('progress-report-preview.pdf');
        }
    }

    /**
     * Generate progress report for a student.
     */
    public function generate(Request $request, Student $student)
    {
        $template = $request->input('template', 'premium-detailed');
        $term = $request->input('term', 'first');
        
        // Here you would fetch actual grade data from database
        // For now, using sample data
        $subjects = [
            ['name' => 'Mathematics', 'obtained_marks' => 85, 'total_marks' => 100, 'grade' => 'A'],
            ['name' => 'Physics', 'obtained_marks' => 78, 'total_marks' => 100, 'grade' => 'B+'],
            ['name' => 'Chemistry', 'obtained_marks' => 82, 'total_marks' => 100, 'grade' => 'A-'],
            ['name' => 'English', 'obtained_marks' => 88, 'total_marks' => 100, 'grade' => 'A'],
            ['name' => 'Urdu', 'obtained_marks' => 75, 'total_marks' => 100, 'grade' => 'B+']
        ];

        $reportData = [
            'subjects' => $subjects,
            'total_obtained' => array_sum(array_column($subjects, 'obtained_marks')),
            'total_marks' => array_sum(array_column($subjects, 'total_marks')),
            'percentage' => round((array_sum(array_column($subjects, 'obtained_marks')) / array_sum(array_column($subjects, 'total_marks'))) * 100, 2),
            'overall_grade' => 'A',
            'position' => 3,
            'total_students' => 45,
            'exam_term' => ucfirst($term) . ' Term',
            'academic_year' => '2024-25',
            'attendance_percentage' => 92.5,
            'remarks' => 'Good performance overall.',
            'generated_on' => date('d M, Y')
        ];
        
        try {
            $pdf = PDF::loadView("progress-reports.{$template}", [
                'student' => $student,
                'reportData' => $reportData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            $filename = "progress-report-{$student->roll_number}-{$term}-term.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate progress report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 