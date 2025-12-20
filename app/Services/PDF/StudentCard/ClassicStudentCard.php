<?php

namespace App\Services\PDF\StudentCard;

class ClassicStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Border
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.5);
        $this->Rect(5, 5, 138, 95);

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 35);
        }

        // Title
        $this->SetFont('times', 'B', 20);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT IDENTIFICATION', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Decorative line
        $this->SetDrawColor(0, 0, 0);
        $this->Line(20, 30, 128, 30);

        // Photo
        if ($this->photo) {
            $this->Image($this->photo, 15, 35, 45);
        }

        // Student Info
        $this->SetFont('times', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(35);
        $this->Cell(0, 8, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(43);
        $this->Cell(0, 8, 'ID Number: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(51);
        $this->Cell(0, 8, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(59);
        $this->Cell(0, 8, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(67);
        $this->Cell(0, 8, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code
        if ($this->qrCode) {
            $this->Image($this->qrCode, 100, 35, 35);
        }

        // Signature
        if ($this->signature) {
            $this->Image($this->signature, 100, 75, 35);
            $this->SetFont('times', 'I', 8);
            $this->SetTextColor(0, 0, 0);
            $this->SetY(85);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }
    }
} 