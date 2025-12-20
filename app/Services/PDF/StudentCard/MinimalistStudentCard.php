<?php

namespace App\Services\PDF\StudentCard;

class MinimalistStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Clean white background
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, 148, 105, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 10, 10, 25);
        }

        // Title
        $this->SetFont('helvetica', 'L', 16);
        $this->SetTextColor(50, 50, 50);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Thin line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, 30, 138, 30);

        // Photo
        if ($this->photo) {
            $this->Image($this->photo, 10, 35, 40);
        }

        // Student Info
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(35);
        $this->Cell(0, 6, $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(41);
        $this->Cell(0, 6, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(47);
        $this->Cell(0, 6, $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(53);
        $this->Cell(0, 6, $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(59);
        $this->Cell(0, 6, 'Valid: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code
        if ($this->qrCode) {
            $this->Image($this->qrCode, 100, 35, 35);
        }

        // Signature
        if ($this->signature) {
            $this->Image($this->signature, 100, 75, 35);
            $this->SetFont('helvetica', 'I', 7);
            $this->SetTextColor(150, 150, 150);
            $this->SetY(85);
            $this->Cell(0, 4, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }
    }
} 