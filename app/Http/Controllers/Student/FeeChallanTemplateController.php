<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class FeeChallanTemplateController extends Controller
{
    /**
     * Get available fee challan templates.
     */
    public function getTemplates()
    {
        $templates = [
            [
                'id' => 'professional-modern',
                'name' => 'Professional Modern',
                'description' => 'Clean professional design with modern elements',
                'premium' => false,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'professional-modern'])
            ],
            [
                'id' => 'corporate-elite',
                'name' => 'Corporate Elite',
                'description' => 'Premium corporate style with elegant borders',
                'premium' => true,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'corporate-elite'])
            ],
            [
                'id' => 'school-classic',
                'name' => 'School Classic',
                'description' => 'Traditional school design with institutional feel',
                'premium' => false,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'school-classic'])
            ],
            [
                'id' => 'digital-receipt',
                'name' => 'Digital Receipt',
                'description' => 'Modern digital receipt style with QR codes',
                'premium' => true,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'digital-receipt'])
            ],
            [
                'id' => 'minimal-clean',
                'name' => 'Minimal Clean',
                'description' => 'Minimalist design with clean typography',
                'premium' => false,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'minimal-clean'])
            ],
            [
                'id' => 'colorful-vibrant',
                'name' => 'Colorful Vibrant',
                'description' => 'Vibrant colors with modern gradient effects',
                'premium' => true,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'colorful-vibrant'])
            ],
            [
                'id' => 'invoice-style',
                'name' => 'Invoice Style',
                'description' => 'Professional invoice layout with detailed sections',
                'premium' => false,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'invoice-style'])
            ],
            [
                'id' => 'luxury-premium',
                'name' => 'Luxury Premium',
                'description' => 'Luxurious design with gold accents and premium feel',
                'premium' => true,
                'preview_url' => route('api.fee-challan.preview', ['template' => 'luxury-premium'])
            ]
        ];

        return response()->json([
            'success' => true,
            'result' => $templates
        ]);
    }

    /**
     * Preview a fee challan template.
     */
    public function previewTemplate(Request $request)
    {
        $template = $request->input('template', 'professional-modern');
        
        // Create sample student data for preview
        $sampleStudent = (object)[
            'id' => 1,
            'first_name' => 'Muhammad',
            'last_name' => 'Ahmad',
            'roll_number' => 'STD-2024-001',
            'class' => (object)['name' => 'Class X'],
            'section' => 'A',
            'father_name' => 'Abdul Rahman',
            'phone' => '+92 300 1234567'
        ];

        $sampleFeeData = [
            'tuition_fee' => 5000,
            'transport_fee' => 1500,
            'examination_fee' => 500,
            'library_fee' => 300,
            'sports_fee' => 200,
            'total_amount' => 7500,
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'month' => date('F Y'),
            'challan_number' => 'CH-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)
        ];

        try {
            $pdf = PDF::loadView("fee-challans.{$template}", [
                'student' => $sampleStudent,
                'feeData' => $sampleFeeData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('fee-challan-preview.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="fee-challan-preview.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback to default template
            $pdf = PDF::loadView("fee-challans.professional-modern", [
                'student' => $sampleStudent,
                'feeData' => $sampleFeeData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            return $pdf->stream('fee-challan-preview.pdf');
        }
    }

    /**
     * Generate fee challan for a student.
     */
    public function generate(Request $request, Student $student)
    {
        $template = $request->input('template', 'professional-modern');
        $month = $request->input('month', date('F Y'));
        $type = $request->input('type', 'monthly');
        
        // Calculate fee data based on student
        $feeData = [
            'tuition_fee' => 5000,
            'transport_fee' => 1500,
            'examination_fee' => 500,
            'library_fee' => 300,
            'sports_fee' => 200,
            'total_amount' => 7500,
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'month' => $month,
            'challan_number' => 'CH-' . date('Y') . '-' . str_pad($student->id, 4, '0', STR_PAD_LEFT)
        ];
        
        try {
            $pdf = PDF::loadView("fee-challans.{$template}", [
                'student' => $student,
                'feeData' => $feeData
            ]);

            $pdf->setPaper('a4', 'portrait');
            
            $filename = "fee-challan-{$student->roll_number}-{$month}.pdf";
            
            return $pdf->stream($filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate fee challan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 