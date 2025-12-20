<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class GeometricTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Geometric background pattern
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 0, 210, 50, 'F');
        
        // Geometric shapes
        $this->SetFillColor(52, 152, 219);
        $this->Rect(0, 0, 40, 40, 'F');
        $this->SetFillColor(41, 128, 185);
        $this->Rect(40, 0, 40, 30, 'F');
        $this->SetFillColor(93, 173, 226);
        $this->Rect(80, 0, 40, 20, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in geometric style
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in geometric style
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, '📞 ' . $this->companyPhone . ' | 📧 ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Geometric bottom border
        $this->SetFillColor(52, 152, 219);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 