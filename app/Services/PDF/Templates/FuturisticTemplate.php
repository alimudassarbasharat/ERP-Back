<?php

namespace App\Services\PDF\Templates;

use App\Services\PDF\BasePDFTemplate;

class FuturisticTemplate extends BasePDFTemplate
{
    public function Header()
    {
        // Futuristic background
        $this->SetFillColor(13, 17, 23);
        $this->Rect(0, 0, 210, 50, 'F');

        // Futuristic accent lines
        $this->SetDrawColor(88, 166, 255);
        $this->SetLineWidth(0.5);
        $this->Line(0, 0, 210, 0);
        $this->Line(0, 0, 0, 50);
        $this->Line(210, 0, 210, 50);

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in futuristic style
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(88, 166, 255);
        $this->SetY(15);
        $this->Cell(0, 10, $this->title, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Company Info in futuristic style
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(201, 209, 217);
        $this->SetY(25);
        $this->Cell(0, 5, $this->companyName, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(30);
        $this->Cell(0, 5, $this->companyAddress, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->SetY(35);
        $this->Cell(0, 5, '⚡ ' . $this->companyPhone . ' | ⚡ ' . $this->companyEmail, 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Futuristic bottom border
        $this->SetFillColor(88, 166, 255);
        $this->Rect(0, 48, 210, 2, 'F');
    }
} 