<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class VintageTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Vintage background
        $this->SetFillColor(245, 222, 179);
        $this->Rect(0, 0, 210, 55, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 30);
        }

        // Title in vintage style
        $this->SetFont('times', 'B', 24);
        $this->SetTextColor(139, 69, 19);
        $this->SetY(20);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Decorative line
        $this->SetDrawColor(139, 69, 19);
        $this->SetLineWidth(0.5);
        $this->Line(120, 30, 190, 30);

        // Company Info in vintage style
        $this->SetFont('times', '', 11);
        $this->SetTextColor(101, 67, 33);
        $this->SetY(40);
        $this->Cell(0, 6, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(46);
        $this->Cell(0, 6, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(52);
        $this->Cell(0, 6, 'Telephone: ' . $this->companyPhone . ' • Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Vintage bottom border
        $this->SetDrawColor(139, 69, 19);
        $this->Line(15, 53, 195, 53);
    }
} 