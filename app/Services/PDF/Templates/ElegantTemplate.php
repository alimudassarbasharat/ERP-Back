<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ElegantTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Elegant background
        $this->SetFillColor(245, 245, 245);
        $this->Rect(0, 0, 210, 60, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 25);
        }

        // Title with elegant styling
        $this->SetFont('times', 'B', 28);
        $this->SetTextColor(75, 75, 75);
        $this->SetY(20);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Decorative line
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.2);
        $this->Line(120, 30, 190, 30);

        // Company Info in elegant style
        $this->SetFont('times', '', 11);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(40);
        $this->Cell(0, 6, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(46);
        $this->Cell(0, 6, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(52);
        $this->Cell(0, 6, 'Telephone: ' . $this->companyPhone . ' • Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bottom decorative line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, 58, 195, 58);
    }
} 