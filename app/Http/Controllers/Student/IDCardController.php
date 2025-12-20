<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class IDCardController extends Controller
{
    /**
     * Get available ID card templates.
     */
    public function getTemplates()
    {
        $templates = [
            [
                'id' => 'modern-3d',
                'name' => 'Modern 3D',
                'description' => '3D layered design with holographic effects',
                'premium' => true,
                'preview_url' => route('api.id-card.preview', ['template' => 'modern-3d'])
            ],
            [
                'id' => 'corporate-sleek',
                'name' => 'Corporate Sleek',
                'description' => 'Professional corporate card design',
                'premium' => false,
                'preview_url' => route('api.id-card.preview', ['template' => 'corporate-sleek'])
            ],
            [
                'id' => 'student-vibrant',
                'name' => 'Student Vibrant',
                'description' => 'Colorful and energetic student design',
                'premium' => true,
                'preview_url' => route('api.id-card.preview', ['template' => 'student-vibrant'])
            ],
            [
                'id' => 'classic-photo',
                'name' => 'Classic Photo',
                'description' => 'Traditional ID card with photo frame',
                'premium' => false,
                'preview_url' => route('api.id-card.preview', ['template' => 'classic-photo'])
            ]
        ];

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Preview an ID card template.
     */
    public function previewTemplate(Request $request)
    {
        $template = $request->input('template', 'modern-3d');
        
        // Create sample student data for preview
        $sampleStudent = (object)[
            'id' => 1,
            'first_name' => 'Fatima',
            'last_name' => 'Khan',
            'roll_number' => 'STD-2024-002',
            'class' => (object)['name' => 'Class IX'],
            'section' => 'B',
            'session' => '2024-25'
        ];

        try {
            $pdf = PDF::loadView("id-cards.{$template}", [
                'student' => $sampleStudent,
                'sessionYear' => '2024-25'
            ]);

            $pdf->setPaper([0, 0, 249.45, 155.91], 'landscape'); // ID card size
            
            return $pdf->stream('id-card-preview.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="id-card-preview.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback to default template
            $pdf = PDF::loadView("id-cards.modern-3d", [
                'student' => $sampleStudent,
                'sessionYear' => '2024-25'
            ]);

            $pdf->setPaper([0, 0, 249.45, 155.91], 'landscape');
            
            return $pdf->stream('id-card-preview.pdf');
        }
    }

    /**
     * Generate ID card for a student.
     */
    public function generate(Request $request, Student $student)
    {
        $template = $request->input('template', 'modern-3d');
        $sessionYear = $request->input('session', '2024-25');
        
        try {
            $pdf = PDF::loadView("id-cards.{$template}", [
                'student' => $student,
                'sessionYear' => $sessionYear
            ]);

            $pdf->setPaper([0, 0, 249.45, 155.91], 'landscape'); // ID card size
            
            $filename = "id-card-{$student->roll_number}.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ID card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 