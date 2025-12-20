<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class CleanTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Clean white background
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, 210, 45, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in clean style
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(50, 50, 50);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in clean style
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, $this->companyPhone . ' | ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Simple bottom line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, 42, 195, 42);
    }
} 