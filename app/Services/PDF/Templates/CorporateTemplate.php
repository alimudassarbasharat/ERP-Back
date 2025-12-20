<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class CorporateTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Top border
        $this->SetFillColor(44, 62, 80);
        $this->Rect(0, 0, 210, 5, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 35);
        }

        // Title with underline
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(44, 62, 80);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        
        // Underline
        $this->SetDrawColor(44, 62, 80);
        $this->Line(120, 25, 195, 25);

        // Company Info in corporate style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(40);
        $this->Cell(0, 5, 'T: ' . $this->companyPhone . ' | E: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bottom border
        $this->SetFillColor(44, 62, 80);
        $this->Rect(0, 45, 210, 1, 'F');
    }
} 