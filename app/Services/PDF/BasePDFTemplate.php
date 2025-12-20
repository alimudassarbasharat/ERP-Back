<?php

namespace App\Services\PDF;

use FPDF;

class BasePDFTemplate extends FPDF
{
    protected $logo;
    protected $title;
    protected $companyName;
    protected $companyAddress;
    protected $companyPhone;
    protected $companyEmail;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);
        $this->SetCreator('ERP System');
        $this->SetAuthor('Your Company Name');
        $this->SetTitle('Document');
    }

    public function setLogo($logoPath)
    {
        $this->logo = $logoPath;
        return $this;
    }

    public function SetTitle($title, $isUTF8 = false)
    {
        parent::SetTitle($title, $isUTF8);
        $this->title = $title;
        return $this;
    }

    public function setCompanyName($name)
    {
        $this->companyName = $name;
        return $this;
    }

    public function setCompanyAddress($address)
    {
        $this->companyAddress = $address;
        return $this;
    }

    public function setCompanyPhone($phone)
    {
        $this->companyPhone = $phone;
        return $this;
    }

    public function setCompanyEmail($email)
    {
        $this->companyEmail = $email;
        return $this;
    }

    public function setCompanyInfo($name, $address, $phone, $email)
    {
        $this->companyName = $name;
        $this->companyAddress = $address;
        $this->companyPhone = $phone;
        $this->companyEmail = $email;
        return $this;
    }

    public function Header()
    {
        // This method will be overridden by child classes
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
} 