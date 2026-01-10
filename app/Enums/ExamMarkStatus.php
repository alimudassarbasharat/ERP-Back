<?php

namespace App\Enums;

enum ExamMarkStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted for Verification',
            self::VERIFIED => 'Verified',
            self::LOCKED => 'Locked',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::SUBMITTED]);
    }

    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canVerify(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canLock(): bool
    {
        return $this === self::VERIFIED;
    }
}
