<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class CreativeTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Creative background pattern
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 0, 210, 50, 'F');
        
        // Diagonal line
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(0, 50, 210, 0);

        // Logo with shadow effect
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 30);
        }

        // Title with creative styling
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(52, 152, 219);
        $this->SetY(20);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info with modern layout
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(35);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(40);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(45);
        $this->Cell(0, 5, '📞 ' . $this->companyPhone . ' | ✉ ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    }
} 