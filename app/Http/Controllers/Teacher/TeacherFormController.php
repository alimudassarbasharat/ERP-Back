<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherFormController extends Controller
{
    public function selectStyle(Request $request, Teacher $teacher)
    {
        $request->validate([
            'style_id' => 'required|integer|between:1,6'
        ]);

        // Store the selected style in the teacher's preferences
        $teacher->update([
            'preferred_form_style' => $request->style_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form style selected successfully'
        ]);
    }

    public function generateForm(Teacher $teacher, $styleId)
    {
        // Validate style ID
        if (!in_array($styleId, range(1, 6))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid form style'
            ], 400);
        }

        // Prepare data for the form
        $data = [
            'profile_image' => $teacher->personal_details->profile_picture 
                ? public_path('storage/' . $teacher->personal_details->profile_picture)
                : public_path('storage/default-profile.png'),
            'employee_name' => $teacher->first_name . ' ' . $teacher->last_name,
            'designation' => $teacher->designation,
            'employee_id' => $teacher->employee_code,
            'dob' => $teacher->personal_details->date_of_birth ?? 'N/A',
            'phone' => $teacher->contact_details->phone_number ?? 'N/A',
            'company_logo' => public_path('storage/logo.png'),
            'company_name' => config('app.name'),
            'company_tagline' => 'Excellence in Education'
        ];

        // Generate PDF based on the selected style
        $pdf = PDF::loadView("employee-id-cards.style{$styleId}", $data);
        $pdfContent = $pdf->output();
        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="teacher-form-' . $styleId . '.pdf"');
    }
} 