<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class LuxuryTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Luxury gold background
        $this->SetFillColor(212, 175, 55);
        $this->Rect(0, 0, 210, 55, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 30);
        }

        // Title in luxury style
        $this->SetFont('times', 'B', 26);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(20);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in elegant style
        $this->SetFont('times', '', 11);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(35);
        $this->Cell(0, 6, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(41);
        $this->Cell(0, 6, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(47);
        $this->Cell(0, 6, 'Tel: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Decorative bottom border
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.5);
        $this->Line(15, 53, 195, 53);
    }
} 