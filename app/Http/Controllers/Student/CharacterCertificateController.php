<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CharacterCertificateController extends Controller
{
    /**
     * Get available character certificate templates.
     */
    public function getTemplates()
    {
        $templates = [];
        for ($i = 1; $i <= 8; $i++) {
            $templates[] = [
                'id' => 'modern-' . $i,
                'name' => 'Modern Style ' . $i,
                'description' => 'Modern Pakistani Character Certificate Design ' . $i,
                'premium' => $i > 2,
                'preview_url' => route('api.character-certificate.preview', ['template' => 'modern-' . $i])
            ];
        }

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Preview a character certificate template.
     */
    public function previewTemplate(Request $request, $templateId = null)
    {
        // Get template from route parameter or request
        $template = $templateId ?? $request->input('template', 'modern-1');
        
        // Validate template exists
        $availableTemplates = [];
        for ($i = 1; $i <= 8; $i++) {
            $availableTemplates[] = 'modern-' . $i;
        }
        if (!in_array($template, $availableTemplates)) {
            $template = 'modern-1';
        }
        
        // Create sample student data for preview
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
            // Return HTML view for iframe preview instead of PDF
            $view = view("character-certificates.{$template}", [
                'student' => $sampleStudent,
                'school' => $school
            ]);
            
            // Add iframe-friendly headers
            return response($view)
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Content-Security-Policy', "frame-ancestors 'self'")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
                
        } catch (\Exception $e) {
            // Fallback to default template
            $view = view("character-certificates.modern-gradient", [
                'student' => $sampleStudent,
                'school' => $school
            ]);
            
            return response($view)
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Content-Security-Policy', "frame-ancestors 'self'");
        }
    }

    /**
     * Generate character certificate for a student.
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
        
        try {
            $pdf = PDF::loadView("character-certificates.{$template}", [
                'student' => $student,
                'school' => $school
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            $filename = "character-certificate-{$student->roll_number}.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate character certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 