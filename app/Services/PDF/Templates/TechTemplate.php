<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class TechTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Tech-style header with dark background
        $this->SetFillColor(26, 32, 44);
        $this->Rect(0, 0, 210, 50, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title with tech styling
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(66, 153, 225);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in modern tech style
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(160, 174, 192);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, '📱 ' . $this->companyPhone . ' | 💻 ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Tech-style bottom border
        $this->SetFillColor(66, 153, 225);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 