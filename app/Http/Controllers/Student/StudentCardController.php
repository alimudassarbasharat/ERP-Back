<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\PDF\StudentCard\ClassicStudentCard;
use App\Services\PDF\StudentCard\ModernStudentCard;
use App\Services\PDF\StudentCard\ProfessionalStudentCard;
use App\Services\PDF\StudentCard\CorporateStudentCard;
use App\Services\PDF\StudentCard\MinimalistStudentCard;
use App\Services\PDF\StudentCard\CreativeStudentCard;
use App\Services\PDF\StudentCard\TechStudentCard;
use App\Services\PDF\StudentCard\ElegantStudentCard;
use App\Services\PDF\StudentCard\LuxuryStudentCard;
use App\Services\PDF\StudentCard\FuturisticStudentCard;
use Illuminate\Http\Request;

class StudentCardController extends Controller
{
    protected $templates = [
        'classic' => ClassicStudentCard::class,
        'modern' => ModernStudentCard::class,
        'professional' => ProfessionalStudentCard::class,
        'corporate' => CorporateStudentCard::class,
        'minimalist' => MinimalistStudentCard::class,
        'creative' => CreativeStudentCard::class,
        'tech' => TechStudentCard::class,
        'elegant' => ElegantStudentCard::class,
        'luxury' => LuxuryStudentCard::class,
        'futuristic' => FuturisticStudentCard::class,
    ];

    public function generate(Student $student, Request $request)
    {
        $templateId = $request->query('template', 'classic');
        
        if (!isset($this->templates[$templateId])) {
            return response()->json(['error' => 'Invalid template'], 400);
        }

        $templateClass = $this->templates[$templateId];
        $pdf = new $templateClass();

        // Set student information
        $pdf->setStudentInfo(
            $student->name,
            $student->student_id,
            $student->department->name,
            $student->program->name,
            $student->valid_until
        );

        // Set logo
        if ($student->institution->logo_path) {
            $pdf->setLogo(storage_path('app/' . $student->institution->logo_path));
        }

        // Set photo
        if ($student->photo_path) {
            $pdf->setPhoto(storage_path('app/' . $student->photo_path));
        }

        // Generate QR code
        $qrCode = $this->generateQRCode($student);
        $pdf->setQRCode($qrCode);

        // Set signature
        if ($student->institution->signature_path) {
            $pdf->setSignature(storage_path('app/' . $student->institution->signature_path));
        }

        // Generate PDF
        $pdfContent = $pdf->Output('', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="student-card.pdf"');
    }

    protected function generateQRCode($student)
    {
        // Generate QR code with student information
        $qrData = [
            'id' => $student->student_id,
            'name' => $student->name,
            'department' => $student->department->name,
            'program' => $student->program->name,
            'valid_until' => $student->valid_until
        ];

        // You can use any QR code library here
        // For example: SimpleSoftwareIO/simple-qrcode
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->generate(json_encode($qrData));

        // Save QR code temporarily
        $tempPath = storage_path('app/temp/qr-' . uniqid() . '.png');
        file_put_contents($tempPath, $qrCode);

        return $tempPath;
    }
} 