<?php

namespace App\Services\PDF\StudentCard;

class CorporateStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Corporate header
        $this->SetFillColor(44, 62, 80);
        $this->Rect(0, 0, 148, 105, 'F');

        // White content area
        $this->SetFillColor(255, 255, 255);
        $this->Rect(5, 5, 138, 95, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 35);
        }

        // Title
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(44, 62, 80);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID CARD', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Corporate line
        $this->SetDrawColor(44, 62, 80);
        $this->Line(15, 30, 133, 30);

        // Photo
        if ($this->photo) {
            $this->Image($this->photo, 15, 35, 45);
        }

        // Student Info
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(44, 62, 80);
        $this->SetY(35);
        $this->Cell(0, 7, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(42);
        $this->Cell(0, 7, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(49);
        $this->Cell(0, 7, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(56);
        $this->Cell(0, 7, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(63);
        $this->Cell(0, 7, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code
        if ($this->qrCode) {
            $this->Image($this->qrCode, 100, 35, 35);
        }

        // Signature
        if ($this->signature) {
            $this->Image($this->signature, 100, 75, 35);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->SetY(85);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Corporate bottom border
        $this->SetFillColor(44, 62, 80);
        $this->Rect(5, 95, 138, 5, 'F');
    }
} 