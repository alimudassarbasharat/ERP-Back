<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ModernBusinessTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Modern header with gradient
        $this->SetFillColor(45, 55, 72);
        $this->Rect(0, 0, 210, 45, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 30);
        }

        // Title in white
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in light gray
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(200, 200, 200);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, 'Phone: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bottom accent line
        $this->SetFillColor(66, 153, 225);
        $this->Rect(0, 45, 210, 2, 'F');
    }
} 