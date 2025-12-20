<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LeavingCertificateController extends Controller
{
    /**
     * Get available leaving certificate templates.
     */
    public function getTemplates()
    {
        $templates = [];
        for ($i = 1; $i <= 8; $i++) {
            $templates[] = [
                'id' => 'modern-' . $i,
                'name' => 'Modern Style ' . $i,
                'description' => 'Modern Pakistani Leaving Certificate Design ' . $i,
                'premium' => $i > 2,
                'preview_url' => route('api.leaving-certificate.preview', ['template' => 'modern-' . $i])
            ];
        }

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Preview a leaving certificate template.
     */
    public function previewTemplate(Request $request, $templateId = null)
    {
        $template = $templateId ?? $request->input('template', 'modern-1');
        $availableTemplates = [];
        for ($i = 1; $i <= 8; $i++) {
            $availableTemplates[] = 'modern-' . $i;
        }
        if (!in_array($template, $availableTemplates)) {
            $template = 'modern-1';
        }
        // Sample student data for preview
        $sampleStudent = (object)[
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'roll_number' => 'STD-2024-001',
            'father_name' => 'Muhammad Ali',
            'class' => (object)['name' => 'Class X-A'],
            'section' => 'A',
            'session' => '2024-25'
        ];
        // Get school data
        $school = (object)[
            'name' => auth()->user()->merchant->name ?? 'School Name',
            'address' => auth()->user()->merchant->address ?? 'School Address',
            'phone' => auth()->user()->merchant->phone ?? 'School Phone',
            'email' => auth()->user()->merchant->email ?? 'School Email',
            'website' => auth()->user()->merchant->website ?? 'School Website',
            'logo' => auth()->user()->merchant->logo ?? null
        ];
        try {
            $view = view("leaving-certificates.{$template}", [
                'student' => $sampleStudent,
                'school' => $school
            ]);
            return response($view)
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Content-Security-Policy', "frame-ancestors 'self'")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            // Fallback to default template
            $view = view("leaving-certificates.modern-1", [
                'student' => $sampleStudent,
                'school' => $school
            ]);
            return response($view)
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Content-Security-Policy', "frame-ancestors 'self'");
        }
    }

    /**
     * Generate leaving certificate for a student.
     */
    public function generate(Request $request, Student $student)
    {
        $template = $request->input('template', 'modern-1');
        $availableTemplates = [];
        for ($i = 1; $i <= 8; $i++) {
            $availableTemplates[] = 'modern-' . $i;
        }
        if (!in_array($template, $availableTemplates)) {
            $template = 'modern-1';
        }
        // Get school data
        $school = (object)[
            'name' => auth()->user()->merchant->name ?? 'School Name',
            'address' => auth()->user()->merchant->address ?? 'School Address',
            'phone' => auth()->user()->merchant->phone ?? 'School Phone',
            'email' => auth()->user()->merchant->email ?? 'School Email',
            'website' => auth()->user()->merchant->website ?? 'School Website',
            'logo' => auth()->user()->merchant->logo ?? null
        ];
        $reason = $request->input('reason', 'Transfer');
        $lastAttendance = $request->input('last_attendance', date('Y-m-d'));
        
        $certificateData = [
            'reason_for_leaving' => $reason,
            'last_attendance_date' => $lastAttendance,
            'conduct' => 'Good',
            'character' => 'Good',
            'academic_performance' => 'Satisfactory',
            'dues_clearance' => 'All dues cleared',
            'certificate_number' => 'LC-' . date('Y') . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT),
            'issue_date' => date('Y-m-d'),
            'session_year' => '2024-25',
            'remarks' => 'No remarks.'
        ];
        
        try {
            $pdf = PDF::loadView("leaving-certificates.{$template}", [
                'student' => $student,
                'certificateData' => $certificateData,
                'school' => $school
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            $filename = "leaving-certificate-{$student->roll_number}.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate leaving certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 