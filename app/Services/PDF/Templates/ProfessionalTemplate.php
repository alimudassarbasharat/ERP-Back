<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ProfessionalTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Background color for header
        $this->SetFillColor(41, 128, 185);
        $this->Rect(0, 0, 210, 40, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 30);
        }

        // Title in white
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in white
        $this->SetFont('helvetica', '', 10);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, $this->companyPhone . ' | ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Reset text color for content
        $this->SetTextColor(0, 0, 0);
    }
} 