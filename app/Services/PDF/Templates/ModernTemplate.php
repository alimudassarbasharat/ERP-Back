<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ModernTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Set background color to modern blue
        $this->SetFillColor(41, 128, 185);
        $this->Rect(0, 0, 210, 40, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Company name in modern style
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company details in modern style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(230, 230, 230);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, 'Tel: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Line break
        $this->Ln(20);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
} 