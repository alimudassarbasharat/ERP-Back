<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class MinimalistTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Set background color to light gray
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 0, 210, 30, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 20);
        }

        // Company name in minimalist style
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(80, 80, 80);
        $this->SetY(15);
        $this->Cell(0, 10, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company details in minimalist style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(120, 120, 120);
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