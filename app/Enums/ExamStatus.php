<?php

namespace App\Enums;

enum ExamStatus: string
{
    case DRAFT = 'draft';
    case RUNNING = 'running';
    case LOCKED = 'locked';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::RUNNING => 'Running',
            self::LOCKED => 'Locked',
            self::PUBLISHED => 'Published',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::RUNNING]);
    }

    public function canLock(): bool
    {
        return in_array($this, [self::DRAFT, self::RUNNING]);
    }

    public function canPublish(): bool
    {
        return $this === self::LOCKED;
    }
}
