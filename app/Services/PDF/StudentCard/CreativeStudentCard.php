<?php

namespace App\Services\PDF\StudentCard;

class CreativeStudentCard extends BaseStudentCardTemplate
{
    public function Header()
    {
        // Creative background pattern
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 0, 148, 105, 'F');
        
        // Diagonal line
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(0, 105, 148, 0);

        // Logo
        if ($this->logo) {
            $this->Image($this->logo, 15, 10, 30);
        }

        // Title with creative styling
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(52, 152, 219);
        $this->SetY(15);
        $this->Cell(0, 10, 'STUDENT ID', 0, false, 'R', 0, '', 0, false, 'M', 'M');

        // Photo with creative border
        if ($this->photo) {
            $this->SetDrawColor(52, 152, 219);
            $this->SetLineWidth(0.5);
            $this->Rect(15, 30, 45, 45);
            $this->Image($this->photo, 15, 30, 45);
        }

        // Student Info with modern layout
        $this->SetFont('helvetica', 'B', 11);
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

        // QR Code with creative border
        if ($this->qrCode) {
            $this->SetDrawColor(52, 152, 219);
            $this->SetLineWidth(0.5);
            $this->Rect(100, 30, 35, 35);
            $this->Image($this->qrCode, 100, 30, 35);
        }

        // Signature with creative styling
        if ($this->signature) {
            $this->Image($this->signature, 100, 70, 35);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(52, 152, 219);
            $this->SetY(80);
            $this->Cell(0, 5, 'Authorized Signature', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Creative bottom border
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(15, 95, 133, 95);
    }
} 