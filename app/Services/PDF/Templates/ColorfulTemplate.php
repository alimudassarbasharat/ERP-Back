<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class ColorfulTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Colorful header with multiple colors
        $this->SetFillColor(255, 99, 132);
        $this->Rect(0, 0, 70, 50, 'F');
        $this->SetFillColor(54, 162, 235);
        $this->Rect(70, 0, 70, 50, 'F');
        $this->SetFillColor(255, 206, 86);
        $this->Rect(140, 0, 70, 50, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in white
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in white
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, '📞 ' . $this->companyPhone . ' | 📧 ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bottom accent line
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 