<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentForm;
use App\Models\Student;
use App\Models\ResultSheet;
use App\Models\SubjectMarkSheet;
use App\Models\Attendance;
use PDF;

class StudentFormController extends Controller
{
    public function templates()
    {
        // Return available report card templates
        $templates = [
            [
                'id' => 'default',
                'name' => 'Default Report Card',
                'description' => 'Standard report card template with grades and attendance',
            ],
            [
                'id' => 'modern',
                'name' => 'Modern Report Card',
                'description' => 'Contemporary design with detailed performance analysis',
            ],
            [
                'id' => 'detailed',
                'name' => 'Detailed Report Card',
                'description' => 'Comprehensive report with subject-wise breakdown',
            ],
        ];
        return response()->json(['result' => $templates]);
    }

    public function templatePreview(Request $request)
    {
        $template = $request->input('template', 'default');
        $student = Student::findOrFail($request->student_id);
        
        // Get student's result data
        $resultSheet = ResultSheet::where('student_id', $student->id)
            ->with(['exam', 'subjectMarks'])
            ->latest()
            ->first();

        if (!$resultSheet) {
            return response()->json([
                'error' => 'No result found for this student. Please ensure the student has exam results.'
            ], 404);
        }

        // Get attendance data
        $attendance = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$resultSheet->exam->start_date, $resultSheet->exam->end_date])
            ->get();

        $data = [
            'student' => $student,
            'result' => $resultSheet,
            'attendance' => $attendance,
            'attendance_percentage' => $this->calculateAttendancePercentage($attendance),
        ];

        $pdf = PDF::loadView('report-cards.' . $template, $data);
        return $pdf->stream('report-card-preview.pdf');
    }

    public function generate(Request $request)
    {
        $template = $request->input('template', 'default');
        $student = Student::findOrFail($request->student_id);
        
        // Get student's result data
        $resultSheet = ResultSheet::where('student_id', $student->id)
            ->with(['exam', 'subjectMarks'])
            ->latest()
            ->first();

        if (!$resultSheet) {
            return response()->json([
                'error' => 'No result found for this student. Please ensure the student has exam results.'
            ], 404);
        }

        // Get attendance data
        $attendance = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$resultSheet->exam->start_date, $resultSheet->exam->end_date])
            ->get();

        $data = [
            'student' => $student,
            'result' => $resultSheet,
            'attendance' => $attendance,
            'attendance_percentage' => $this->calculateAttendancePercentage($attendance),
        ];

        $pdf = PDF::loadView('report-cards.' . $template, $data);
        return $pdf->download('report-card.pdf');
    }

    private function calculateAttendancePercentage($attendance)
    {
        if ($attendance->isEmpty()) {
            return 0;
        }

        $total = $attendance->count();
        $present = $attendance->where('status', 'present')->count();
        
        return round(($present / $total) * 100, 2);
    }
} 