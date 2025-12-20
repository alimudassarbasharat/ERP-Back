<?php

namespace App\Services\PDF\StudentCard;

class ProfessionalStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Professional header background
        $this->SetFillColor(45, 55, 72);
        $this->Rect(0, 0, 148, 25, 'F');

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 10, 5, 25);
        }

        // Title
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(10);
        $this->Cell(0, 10, 'STUDENT IDENTIFICATION CARD', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // White content area
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 25, 148, 80, 'F');

        // Photo
        if ($this->photo) {
            $this->Image($this->photo, 10, 30, 45);
        }

        // Student Info
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(45, 55, 72);
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
            $this->Image($this->qrCode, 100, 30, 35);
        }

        // Signature
        if ($this->signature) {
            $this->Image($this->signature, 100, 70, 35);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->SetY(80);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Bottom border
        $this->SetFillColor(45, 55, 72);
        $this->Rect(0, 100, 148, 5, 'F');
    }
} 