<?php

namespace App\Http\Controllers;

use App\Services\PDF\Templates\ModernTemplate;
use App\Services\PDF\Templates\ClassicTemplate;
use App\Services\PDF\Templates\MinimalistTemplate;
use App\Services\PDF\Templates\ProfessionalTemplate;
use App\Services\PDF\Templates\CorporateTemplate;
use App\Services\PDF\Templates\CreativeTemplate;
use App\Services\PDF\Templates\ElegantTemplate;
use App\Services\PDF\Templates\ModernBusinessTemplate;
use App\Services\PDF\Templates\TechTemplate;
use App\Services\PDF\Templates\LuxuryTemplate;
use App\Services\PDF\Templates\CleanTemplate;
use App\Services\PDF\Templates\BoldTemplate;
use App\Services\PDF\Templates\GradientTemplate;
use App\Services\PDF\Templates\DarkTemplate;
use App\Services\PDF\Templates\ModernMinimalistTemplate;
use App\Services\PDF\Templates\ColorfulTemplate;
use App\Services\PDF\Templates\GeometricTemplate;
use App\Services\PDF\Templates\VintageTemplate;
use App\Services\PDF\Templates\FuturisticTemplate;
use App\Services\PDF\Templates\PlayfulTemplate;

use App\Services\PDF\StudentCard\ModernStudentCard;
use App\Services\PDF\StudentCard\ClassicStudentCard;
use App\Services\PDF\StudentCard\MinimalistStudentCard;
use App\Services\PDF\StudentCard\ProfessionalStudentCard;
use App\Services\PDF\StudentCard\CorporateStudentCard;
use App\Services\PDF\StudentCard\CreativeStudentCard;
use App\Services\PDF\StudentCard\ElegantStudentCard;
use App\Services\PDF\StudentCard\TechStudentCard;
use App\Services\PDF\StudentCard\LuxuryStudentCard;
use App\Services\PDF\StudentCard\FuturisticStudentCard;

class PDFTestController extends Controller
{
    protected function getImagePath($type)
    {
        $path = public_path("images/sample_{$type}.png");
        return file_exists($path) ? $path : null;
    }

    public function testAllTemplates()
    {
        $pdfTemplates = [
            'modern' => 'Modern Template',
            'classic' => 'Classic Template',
            'minimalist' => 'Minimalist Template',
        ];

        $studentCardTemplates = [
            'modern' => 'Modern Student Card',
            'classic' => 'Classic Student Card',
            'minimalist' => 'Minimalist Student Card',
        ];

        return view('pdf.test', compact('pdfTemplates', 'studentCardTemplates'));
    }

    public function testPDFTemplate($template)
    {
        $templateClass = $this->getPDFTemplateClass($template);
        if (!$templateClass || !class_exists($templateClass)) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $pdf = new $templateClass();
        
        // Set document information
        $pdf->SetTitle('Test Document');
        $pdf->SetAuthor('Test Company');
        $pdf->SetCreator('Test Application');
        $pdf->AliasNbPages();
        
        // Set company information
        $pdf->setCompanyInfo(
            'Test Company Ltd.',
            '123 Test Street, Test City, 12345',
            '+1 234 567 8900',
            'contact@testcompany.com'
        );
    
        // Set logo if available
        $logoPath = $this->getImagePath('logo');
        if ($logoPath) {
            $pdf->setLogo($logoPath);
        }
    
        // Add a page and content
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        // Sample content with proper formatting
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Test Document', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Document Details:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(40, 10, 'Date:', 0);
        $pdf->Cell(0, 10, date('Y-m-d'), 0, 1);
        $pdf->Cell(40, 10, 'Reference:', 0);
        $pdf->Cell(0, 10, 'REF-' . date('Ymd') . '-001', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Sample Content:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'This is a test document generated using the ' . $template . ' template. It includes various formatting elements to demonstrate the template\'s capabilities.');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Sample Table:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 10, 'Item', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Quantity', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Price', 1, 1, 'C', true);
        
        // Table data
        $items = [
            ['Test Item 1', '2', '$100.00'],
            ['Test Item 2', '1', '$150.00'],
            ['Test Item 3', '3', '$75.00'],
        ];
        
        foreach ($items as $item) {
            $pdf->Cell(60, 10, $item[0], 1);
            $pdf->Cell(40, 10, $item[1], 1, 0, 'C');
            $pdf->Cell(40, 10, $item[2], 1, 1, 'R');
        }
    
        // Generate PDF content
        $pdfContent = $pdf->Output('S', 'test_document.pdf');
    
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="test_document.pdf"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }

    public function testStudentCard($template)
    {
        $templateClass = $this->getStudentCardTemplateClass($template);
        if (!$templateClass) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $pdf = new $templateClass();
        
        // Set student information with realistic data
        $pdf->setName('John Smith');
        $pdf->setIdNumber('STU' . date('Y') . '001');
        $pdf->setDepartment('Computer Science');
        $pdf->setProgram('Bachelor of Science in Computer Science');
        $pdf->setValidityDate(date('Y-m-d', strtotime('+1 year')));

        // Set images if available
        $logoPath = $this->getImagePath('logo');
        $photoPath = $this->getImagePath('photo');
        $qrPath = $this->getImagePath('qr');
        $signaturePath = $this->getImagePath('signature');

        if ($logoPath) $pdf->setLogo($logoPath);
        if ($photoPath) $pdf->setPhoto($photoPath);
        if ($qrPath) $pdf->setQRCode($qrPath);
        if ($signaturePath) $pdf->setSignature($signaturePath);

        // Generate PDF content
        $pdfContent = $pdf->Output('S', 'student_card.pdf');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="student_card.pdf"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }

    protected function getPDFTemplateClass($template)
    {
        $templates = [
            'modern' => ModernTemplate::class,
            'classic' => ClassicTemplate::class,
            'minimalist' => MinimalistTemplate::class,
        ];

        return $templates[$template] ?? null;
    }

    protected function getStudentCardTemplateClass($template)
    {
        $templates = [
            'modern' => ModernStudentCard::class,
            'classic' => ClassicStudentCard::class,
            'minimalist' => MinimalistStudentCard::class,
        ];

        return $templates[$template] ?? null;
    }
} 