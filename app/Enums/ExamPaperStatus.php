<?php

namespace App\Enums;

enum ExamPaperStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted for Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LOCKED => 'Locked',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canLock(): bool
    {
        return $this === self::APPROVED;
    }
}
