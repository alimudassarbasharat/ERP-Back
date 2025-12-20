<?php

namespace App\Services\PDF\StudentCard;

class ElegantStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Elegant background
        $this->SetFillColor(245, 245, 245);
        $this->Rect(0, 0, 148, 105, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 20, 15, 25);
        }

        // Title with elegant styling
        $this->SetFont('times', 'B', 24);
        $this->SetTextColor(75, 75, 75);
        $this->SetY(20);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Decorative line
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.2);
        $this->Line(20, 35, 128, 35);

        // Photo with elegant border
        if ($this->photo) {
            $this->SetDrawColor(150, 150, 150);
            $this->SetLineWidth(0.2);
            $this->Rect(20, 40, 45, 45);
            $this->Image($this->photo, 20, 40, 45);
        }

        // Student Info in elegant style
        $this->SetFont('times', '', 11);
        $this->SetTextColor(100, 100, 100);
        $this->SetY(40);
        $this->Cell(0, 6, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(46);
        $this->Cell(0, 6, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(52);
        $this->Cell(0, 6, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(58);
        $this->Cell(0, 6, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(64);
        $this->Cell(0, 6, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code with elegant border
        if ($this->qrCode) {
            $this->SetDrawColor(150, 150, 150);
            $this->SetLineWidth(0.2);
            $this->Rect(100, 40, 35, 35);
            $this->Image($this->qrCode, 100, 40, 35);
        }

        // Signature with elegant styling
        if ($this->signature) {
            $this->Image($this->signature, 100, 80, 35);
            $this->SetFont('times', 'I', 8);
            $this->SetTextColor(150, 150, 150);
            $this->SetY(90);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Elegant bottom border
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.2);
        $this->Line(20, 95, 128, 95);
    }
} 