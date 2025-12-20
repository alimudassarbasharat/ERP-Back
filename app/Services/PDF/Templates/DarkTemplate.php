<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class DarkTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Dark theme header
        $this->SetFillColor(33, 37, 41);
        $this->Rect(0, 0, 210, 50, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in dark theme
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in dark theme
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(200, 200, 200);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, '📱 ' . $this->companyPhone . ' | 📧 ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Accent line
        $this->SetFillColor(108, 117, 125);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 