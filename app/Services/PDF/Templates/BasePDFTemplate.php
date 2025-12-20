<?php

namespace App\Services\PDF\Templates;

use FPDF;

class BasePDFTemplate extends FPDF
{
    protected $companyName = '';
    protected $companyAddress = '';
    protected $companyPhone = '';
    protected $companyEmail = '';
    protected $logo = null;

    public function setCompanyName(string $name): self
    {
        $this->companyName = $name;
        return $this;
    }

    public function setCompanyAddress(string $address): self
    {
        $this->companyAddress = $address;
        return $this;
    }

    public function setCompanyPhone(string $phone): self
    {
        $this->companyPhone = $phone;
        return $this;
    }

    public function setCompanyEmail(string $email): self
    {
        $this->companyEmail = $email;
        return $this;
    }

    public function setLogo(string $path): self
    {
        if (file_exists($path)) {
            $this->logo = $path;
        }
        return $this;
    }

    public function Header()
    {
        // Default header implementation (can be overridden)
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, $this->companyName, 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer()
    {
        // Default footer implementation (can be overridden)
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    protected function renderWatermark()
    {
        // Example of a reusable method child classes can call
        $this->SetFont('Arial', 'B', 50);
        $this->SetTextColor(230, 230, 230);
        $this->RotatedText(35, 190, 'S A M P L E', 45);
        $this->SetTextColor(0, 0, 0);
    }

    protected function RotatedText($x, $y, $txt, $angle)
    {
        // Text rotated around its origin
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }
}