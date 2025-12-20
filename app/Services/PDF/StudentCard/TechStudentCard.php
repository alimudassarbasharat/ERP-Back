<?php

namespace App\Services\PDF\StudentCard;

class TechStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Tech-style header with dark background
        $this->SetFillColor(26, 32, 44);
        $this->Rect(0, 0, 148, 105, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title with tech styling
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(66, 153, 225);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Tech-style accent lines
        $this->SetDrawColor(66, 153, 225);
        $this->SetLineWidth(0.5);
        $this->Line(15, 30, 133, 30);

        // Photo with tech border
        if ($this->photo) {
            $this->SetDrawColor(66, 153, 225);
            $this->SetLineWidth(0.5);
            $this->Rect(15, 35, 45, 45);
            $this->Image($this->photo, 15, 35, 45);
        }

        // Student Info in tech style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(201, 209, 217);
        $this->SetY(35);
        $this->Cell(0, 6, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(41);
        $this->Cell(0, 6, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(47);
        $this->Cell(0, 6, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(53);
        $this->Cell(0, 6, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(59);
        $this->Cell(0, 6, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code with tech border
        if ($this->qrCode) {
            $this->SetDrawColor(66, 153, 225);
            $this->SetLineWidth(0.5);
            $this->Rect(100, 35, 35, 35);
            $this->Image($this->qrCode, 100, 35, 35);
        }

        // Signature with tech styling
        if ($this->signature) {
            $this->Image($this->signature, 100, 75, 35);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(201, 209, 217);
            $this->SetY(85);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Tech-style bottom border
        $this->SetDrawColor(66, 153, 225);
        $this->SetLineWidth(0.5);
        $this->Line(15, 95, 133, 95);
    }
} 