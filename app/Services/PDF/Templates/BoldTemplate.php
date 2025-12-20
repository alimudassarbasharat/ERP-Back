<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class BoldTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Bold header with strong color
        $this->SetFillColor(220, 53, 69);
        $this->Rect(0, 0, 210, 50, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 30);
        }

        // Title in bold style
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in bold style
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(25);
        $this->Cell(0, 6, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(31);
        $this->Cell(0, 6, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(37);
        $this->Cell(0, 6, '📞 ' . $this->companyPhone . ' | 📧 ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Bold bottom border
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 