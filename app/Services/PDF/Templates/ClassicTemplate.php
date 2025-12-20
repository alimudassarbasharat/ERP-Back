<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ClassicTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 40);
        }

        // Title with decorative line
        $this->SetFont('times', 'B', 24);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(20);
        $this->Cell(0, 10, $this->companyName, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Decorative line
        $this->SetDrawColor(0, 0, 0);
        $this->Line(50, 35, 160, 35);

        // Company Info in classic style
        $this->SetFont('times', '', 12);
        $this->SetY(45);
        $this->Cell(0, 6, $this->companyName, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetY(51);
        $this->Cell(0, 6, $this->companyAddress, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetY(57);
        $this->Cell(0, 6, 'Tel: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Bottom decorative line
        $this->Line(50, 65, 160, 65);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
} 