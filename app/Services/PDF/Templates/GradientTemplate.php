<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class GradientTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Gradient-like header (using multiple rectangles for gradient effect)
        $this->SetFillColor(41, 128, 185);
        $this->Rect(0, 0, 210, 50, 'F');
        $this->SetFillColor(52, 152, 219);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFillColor(93, 173, 226);
        $this->Rect(0, 0, 210, 30, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title with gradient-like effect
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info with modern style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, 'Phone: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bottom accent line
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 