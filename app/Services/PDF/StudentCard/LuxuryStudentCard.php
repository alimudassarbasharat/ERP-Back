<?php

namespace App\Services\PDF\StudentCard;

class LuxuryStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Luxury gold background
        $this->SetFillColor(212, 175, 55);
        $this->Rect(0, 0, 148, 105, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 30);
        }

        // Title in luxury style
        $this->SetFont('times', 'B', 24);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(20);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Photo with luxury border
        if ($this->photo) {
            $this->SetDrawColor(255, 255, 255);
            $this->SetLineWidth(0.5);
            $this->Rect(20, 35, 45, 45);
            $this->Image($this->photo, 20, 35, 45);
        }

        // Student Info in luxury style
        $this->SetFont('times', '', 11);
        $this->SetTextColor(255, 255, 255);
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

        // QR Code with luxury border
        if ($this->qrCode) {
            $this->SetDrawColor(255, 255, 255);
            $this->SetLineWidth(0.5);
            $this->Rect(100, 35, 35, 35);
            $this->Image($this->qrCode, 100, 35, 35);
        }

        // Signature with luxury styling
        if ($this->signature) {
            $this->Image($this->signature, 100, 75, 35);
            $this->SetFont('times', 'I', 8);
            $this->SetTextColor(255, 255, 255);
            $this->SetY(85);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Luxury bottom border
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.5);
        $this->Line(20, 95, 128, 95);
    }
} 