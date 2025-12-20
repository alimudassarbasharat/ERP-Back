<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use \App\Models\FeeDefault;

class FeeChallanController extends Controller
{
    /**
     * Generate a fee challan for a student using the specified template.
     */
    public function generate(Request $request, Student $student)
    {
        // Check section_id and class_id if provided
        if ($request->has('section_id')) {
            $sectionId = $request->input('section_id');
            if ($student->section_id != $sectionId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student does not belong to the specified section.'
                ], 404);
            }
        }
        if ($request->has('class_id')) {
            $classId = $request->input('class_id');
            if ($student->class_id != $classId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student does not belong to the specified class.'
                ], 404);
            }
        }
        $template = $request->input('template', 'default');
        $month = $request->input('month', now()->format('F Y'));
        
        // Get student's fee details
        $feeDetails = $this->getFeeDetails($student);
        
        // Map template names to their corresponding blade views
        $templateMap = [
            'default' => 'fee-challans.default',
            'modern' => 'fee-challans.modern',
            'classic' => 'fee-challans.classic',
            'corporate' => 'fee-challans.corporate',
            'elegant' => 'fee-challans.elegant',
            'medical' => 'fee-challans.medical',
            'minimal' => 'fee-challans.minimal',
            'traditional' => 'fee-challans.traditional',
            'professional' => 'fee-challans.professional',
            'school' => 'fee-challans.school',
            'university' => 'fee-challans.university',
        ];

        // Get the appropriate template view
        $view = $templateMap[$template] ?? $templateMap['default'];

        // Generate PDF
        $pdf = PDF::loadView($view, [
            'student' => $student,
            'feeDetails' => $feeDetails,
            'month' => $month,
            'challanNumber' => $this->generateChallanNumber(),
            'issueDate' => now()->format('d-m-Y'),
            'dueDate' => now()->addDays(7)->format('d-m-Y'),
        ]);

        // Set PDF options for high quality
        $pdf->setPaper('a4');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('dpi', 150);
        $pdf->setOption('defaultFont', 'Arial');

        // Generate filename
        $filename = "fee-challan-{$student->roll_number}-{$month}.pdf";

        // Return the PDF for download
        return $pdf->stream($filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public'
        ]);
    }

    /**
     * Preview a fee challan template.
     */
    public function previewTemplate(Request $request)
    {
        $template = $request->input('template', 'default');
        
        // Map template names to their corresponding blade views
        $templateMap = [
            'default' => 'fee-challans.default',
            'modern' => 'fee-challans.modern',
            'classic' => 'fee-challans.classic',
            'corporate' => 'fee-challans.corporate',
            'elegant' => 'fee-challans.elegant',
            'medical' => 'fee-challans.medical',
            'minimal' => 'fee-challans.minimal',
            'traditional' => 'fee-challans.traditional',
            'professional' => 'fee-challans.professional',
            'school' => 'fee-challans.school',
            'university' => 'fee-challans.university',
        ];

        // Get the appropriate template view
        $view = $templateMap[$template] ?? $templateMap['default'];

        // Generate PDF with sample data
        $pdf = PDF::loadView($view, [
            'student' => (object)[
                'first_name' => 'Sample',
                'last_name' => 'Student',
                'roll_number' => 'SAMPLE-001',
                'reg_form' => 'REG-001',
                'class' => (object)['name' => 'Sample Class'],
                'semester' => '1st',
                'session' => '2024'
            ],
            'feeDetails' => [
                'admission_fee' => 5000,
                'tuition_fee' => 10000,
                'examination_fee' => 2000,
                'library_fee' => 1000,
                'sports_fee' => 500,
                'total_amount' => 18500
            ],
            'challanNumber' => 'CHL-' . date('Ymd') . '-0001',
            'issueDate' => now()->format('d-m-Y'),
            'dueDate' => now()->addDays(7)->format('d-m-Y'),
            'month' => now()->format('F Y')
        ]);

        // Set PDF options for high quality
        $pdf->setPaper('a4');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('dpi', 150);
        $pdf->setOption('defaultFont', 'Arial');

        // Return the PDF for preview
        return $pdf->stream('template-preview.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="template-preview.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public'
        ]);
    }

    /**
     * Get available fee challan templates.
     */
    public function getTemplates()
    {
        $templateList = [
            [
                'id' => 'default',
                'name' => 'Default',
                'description' => 'Standard fee challan format',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'default'])
            ],
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Contemporary design with clean layout',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'modern'])
            ],
            [
                'id' => 'classic',
                'name' => 'Classic',
                'description' => 'Traditional format with formal styling',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'classic'])
            ],
            [
                'id' => 'corporate',
                'name' => 'Corporate',
                'description' => 'Professional business style',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'corporate'])
            ],
            [
                'id' => 'elegant',
                'name' => 'Elegant',
                'description' => 'Sophisticated design with premium feel',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'elegant'])
            ],
            [
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Simple and clean design',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'minimal'])
            ],
            [
                'id' => 'traditional',
                'name' => 'Traditional',
                'description' => 'Classic Pakistani style',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'traditional'])
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'Formal business layout',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'professional'])
            ],
            [
                'id' => 'school',
                'name' => 'School',
                'description' => 'Designed for primary/secondary schools',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'school'])
            ],
            [
                'id' => 'university',
                'name' => 'University',
                'description' => 'Higher education institution format',
                'preview' => route('api.students.fee-challan-template-preview', ['template' => 'university'])
            ],
        ];

        return response()->json([
            'status' => true,
            'message' => 'Fee challan templates fetched successfully.',
            'result' => $templateList
        ]);
    }

    /**
     * Get fee details for a student.
     */
    private function getFeeDetails(Student $student)
    {
        // Get the default fee for the student's class
        $feeDefault = FeeDefault::where('class_id', $student->class_id)->first();

        $tuition_fee = $feeDefault ? $feeDefault->monthly_fee : 0;

        // TODO: Implement logic for other fees if necessary
        return [
            'tuition_fee' => $tuition_fee,
            'examination_fee' => 0, // Placeholder
            'library_fee' => 0,     // Placeholder
            'sports_fee' => 0,      // Placeholder
            'computer_lab_fee' => 0,// Placeholder
            'total_amount' => $tuition_fee, // Sum of all fees
        ];
    }

    /**
     * Generate a unique challan number.
     */
    private function generateChallanNumber()
    {
        return 'CHL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate challans for all students in a class and section.
     */
    public function generateForClassSection(Request $request, $classId, $sectionId)
    {
        $students = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->get();

        // Ensure class_id and section_id are in the request for validation in generate()
        $request->merge([
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);

        $results = [];
        foreach ($students as $student) {
            $result = $this->generate($request, $student); // Pass the model, not the ID!
            $results[] = [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . ($student->last_name ?? ''),
                'result' => $result instanceof \Illuminate\Http\Response ? 'generated' : $result
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Challans generated for all students in class/section.',
            'results' => $results,
        ]);
    }
} 