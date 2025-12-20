<?php

namespace App\Services\PDF\StudentCard;

use TCPDF;

class BaseStudentCardTemplate extends TCPDF
{
    protected $logo;
    protected $studentName;
    protected $studentId;
    protected $department;
    protected $program;
    protected $validUntil;
    protected $photo;
    protected $qrCode;
    protected $signature;

    public function __construct($orientation = 'L', $unit = 'mm', $format = 'A6')
    {
        parent::__construct($orientation, $unit, $format);
        $this->SetCreator('ERP System');
        $this->SetAuthor('Your Institution Name');
        $this->SetTitle('Student ID Card');
    }

    public function setLogo($logoPath)
    {
        $this->logo = $logoPath;
    }

    public function setStudentInfo($name, $id, $department, $program, $validUntil)
    {
        $this->studentName = $name;
        $this->studentId = $id;
        $this->department = $department;
        $this->program = $program;
        $this->validUntil = $validUntil;
    }

    public function setPhoto($photoPath)
    {
        $this->photo = $photoPath;
    }

    public function setQRCode($qrCodePath)
    {
        $this->qrCode = $qrCodePath;
    }

    public function setSignature($signing_cert = '', $private_key = '', $private_key_password = '', $extracerts = '', $cert_type = 2, $info = [], $approval = '')
    {
        // Store the signature path in our custom property
        $this->signature = $signing_cert;
        
        // Call parent method with all parameters
        parent::setSignature($signing_cert, $private_key, $private_key_password, $extracerts, $cert_type, $info, $approval);
    }

    public function Header()
    {
        // This method will be overridden by child classes
    }

    public function Footer()
    {
        // This method will be overridden by child classes
    }
} 