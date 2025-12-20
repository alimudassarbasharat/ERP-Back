<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

class StudentIDCardController extends Controller
{
    protected function getImagePath($type)
    {
        $path = public_path("images/sample_{$type}.png");
        return file_exists($path) ? $path : null;
    }

    public function index()
    {
        $studentCardTemplates = [
            'modern' => 'Modern Student Card',
            'classic' => 'Classic Student Card',
            'minimalist' => 'Minimalist Student Card',
            'professional' => 'Professional Student Card',
            'corporate' => 'Corporate Student Card',
            'creative' => 'Creative Student Card',
            'elegant' => 'Elegant Student Card',
            'tech' => 'Tech Student Card',
            'luxury' => 'Luxury Student Card',
            'futuristic' => 'Futuristic Student Card',
        ];

        return view('student.id-cards.student-cards', compact('studentCardTemplates'));
    }

    public function pdfTemplates()
    {
        $pdfTemplates = [
            'modern' => 'Modern Template',
            'classic' => 'Classic Template',
            'minimalist' => 'Minimalist Template',
            'professional' => 'Professional Template',
            'corporate' => 'Corporate Template',
            'creative' => 'Creative Template',
            'elegant' => 'Elegant Template',
            'modern-business' => 'Modern Business Template',
            'tech' => 'Tech Template',
            'luxury' => 'Luxury Template',
            'clean' => 'Clean Template',
            'bold' => 'Bold Template',
            'gradient' => 'Gradient Template',
            'dark' => 'Dark Template',
            'modern-minimalist' => 'Modern Minimalist Template',
            'colorful' => 'Colorful Template',
            'geometric' => 'Geometric Template',
            'vintage' => 'Vintage Template',
            'futuristic' => 'Futuristic Template',
            'playful' => 'Playful Template',
        ];

        return view('pdf.templates.index', compact('pdfTemplates'));
    }

    public function previewTemplate($type, $template)
    {
        if ($type === 'student-card') {
            return $this->generateStudentCard($template, true);
        } else {
            return $this->generatePDF($template, true);
        }
    }

    public function generateStudentCard($template, $preview = false)
    {
        $templateClass = $this->getStudentCardTemplateClass($template);
        if (!$templateClass) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $pdf = new $templateClass();
        
        // Set student information
        $pdf->setName('John Doe');
        $pdf->setIdNumber('STU2024001');
        $pdf->setDepartment('Computer Science');
        $pdf->setProgram('Bachelor of Science');
        $pdf->setValidityDate('2024-12-31');

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

        // Return response with proper headers
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $preview ? 'inline' : 'attachment; filename="student_card.pdf"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }

    public function generatePDF($template, $preview = false)
    {
        $templateClass = $this->getPDFTemplateClass($template);
        
        if (!$templateClass || !class_exists($templateClass)) {
            return response()->json(['error' => 'Template not found'], 404);
        }
    
        /** @var BasePDFTemplate $pdf */
        $pdf = new $templateClass();
        
        // Set document information
        $pdf->SetTitle('Sample Document');
        $pdf->SetAuthor('Your Company');
        $pdf->SetCreator('Your Application');
        $pdf->AliasNbPages(); // For {nb} in footer
        
        // Set company information
        $pdf->setCompanyInfo(
            'Sample Company',
            '123 Business Street, City, Country',
            '+1 234 567 890',
            'info@samplecompany.com'
        );
    
        // Set logo if available
        $logoPath = $this->getImagePath('logo');
        if ($logoPath) {
            $pdf->setLogo($logoPath);
        }
    
        // Add a page and content
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        
        // Sample content
        $pdf->MultiCell(0, 10, 'This is a sample PDF document generated using the ' . $template . ' template.');
        $pdf->Ln();
        $pdf->MultiCell(0, 10, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris.');
    
        // Generate PDF content
        $pdfContent = $pdf->Output('S', 'document.pdf');
    
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $preview ? 'inline' : 'attachment; filename="document.pdf"')
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public');
    }

    protected function getStudentCardTemplateClass($template)
    {
        $templates = [
            'modern' => ModernStudentCard::class,
            'classic' => ClassicStudentCard::class,
            'minimalist' => MinimalistStudentCard::class,
            'professional' => ProfessionalStudentCard::class,
            'corporate' => CorporateStudentCard::class,
            'creative' => CreativeStudentCard::class,
            'elegant' => ElegantStudentCard::class,
            'tech' => TechStudentCard::class,
            'luxury' => LuxuryStudentCard::class,
            'futuristic' => FuturisticStudentCard::class,
        ];

        return $templates[$template] ?? null;
    }

    protected function getPDFTemplateClass($template)
    {
        $templates = [
            'modern' => ModernTemplate::class,
            'classic' => ClassicTemplate::class,
            'minimalist' => MinimalistTemplate::class,
            'professional' => ProfessionalTemplate::class,
            'corporate' => CorporateTemplate::class,
            'creative' => CreativeTemplate::class,
            'elegant' => ElegantTemplate::class,
            'modern-business' => ModernBusinessTemplate::class,
            'tech' => TechTemplate::class,
            'luxury' => LuxuryTemplate::class,
            'clean' => CleanTemplate::class,
            'bold' => BoldTemplate::class,
            'gradient' => GradientTemplate::class,
            'dark' => DarkTemplate::class,
            'modern-minimalist' => ModernMinimalistTemplate::class,
            'colorful' => ColorfulTemplate::class,
            'geometric' => GeometricTemplate::class,
            'vintage' => VintageTemplate::class,
            'futuristic' => FuturisticTemplate::class,
            'playful' => PlayfulTemplate::class,
        ];

        return $templates[$template] ?? null;
    }
} 