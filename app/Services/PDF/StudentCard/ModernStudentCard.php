<?php

namespace App\Services\PDF\StudentCard;

class ModernStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Background
        $this->SetFillColor(41, 128, 185);
        $this->Rect(0, 0, 148, 105, 'F');

        // White content area
        $this->SetFillColor(255, 255, 255);
        $this->Rect(5, 5, 138, 95, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 10, 10, 30);
        }

        // Institution Name
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(41, 128, 185);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID CARD', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Photo
        if ($this->photo) {
            $this->Image($this->photo, 10, 30, 40);
        }

        // Student Info
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(50, 50, 50);
        $this->SetY(30);
        $this->Cell(0, 7, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(37);
        $this->Cell(0, 7, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(44);
        $this->Cell(0, 7, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(51);
        $this->Cell(0, 7, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(58);
        $this->Cell(0, 7, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code
        if ($this->qrCode) {
            $this->Image($this->qrCode, 110, 30, 30);
        }

        // Signature
        if ($this->signature) {
            $this->Image($this->signature, 110, 65, 30);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->SetY(80);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }
    }
} 