<?php

namespace App\Services\PDF\StudentCard;

class FuturisticStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Futuristic background
        $this->SetFillColor(13, 17, 23);
        $this->Rect(0, 0, 148, 105, 'F');

        // Futuristic accent lines
        $this->SetDrawColor(88, 166, 255);
        $this->SetLineWidth(0.5);
        $this->Line(0, 0, 148, 0);
        $this->Line(0, 0, 0, 105);
        $this->Line(148, 0, 148, 105);

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 25);
        }

        // Title in futuristic style
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(88, 166, 255);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Photo with futuristic border
        if ($this->photo) {
            $this->SetDrawColor(88, 166, 255);
            $this->SetLineWidth(0.5);
            $this->Rect(15, 30, 45, 45);
            $this->Image($this->photo, 15, 30, 45);
        }

        // Student Info in futuristic style
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(201, 209, 217);
        $this->SetY(30);
        $this->Cell(0, 6, 'Name: ' . $this->studentName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(36);
        $this->Cell(0, 6, 'ID: ' . $this->studentId, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(42);
        $this->Cell(0, 6, 'Department: ' . $this->department, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(48);
        $this->Cell(0, 6, 'Program: ' . $this->program, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->SetY(54);
        $this->Cell(0, 6, 'Valid Until: ' . $this->validUntil, 0, false, 'L', 0, '', 0, false, 'M', 'M');

        // QR Code with futuristic border
        if ($this->qrCode) {
            $this->SetDrawColor(88, 166, 255);
            $this->SetLineWidth(0.5);
            $this->Rect(100, 30, 35, 35);
            $this->Image($this->qrCode, 100, 30, 35);
        }

        // Signature with futuristic styling
        if ($this->signature) {
            $this->Image($this->signature, 100, 70, 35);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(201, 209, 217);
            $this->SetY(80);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Futuristic bottom border
        $this->SetDrawColor(88, 166, 255);
        $this->SetLineWidth(0.5);
        $this->Line(15, 90, 133, 90);
    }
} 