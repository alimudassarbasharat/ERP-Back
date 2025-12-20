<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ModernMinimalistTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Clean white background
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, 210, 45, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 20);
        }

        // Title in modern minimalist style
        $this->SetFont('helvetica', 'L', 16);
        $this->SetTextColor(40, 40, 40);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in minimalist style
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->SetY(25);
        $this->Cell(0, 4, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(29);
        $this->Cell(0, 4, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(33);
        $this->Cell(0, 4, $this->companyPhone . ' · ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Minimalist bottom line
        $this->SetDrawColor(230, 230, 230);
        $this->Line(15, 40, 195, 40);
    }
} 