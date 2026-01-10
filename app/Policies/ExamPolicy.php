<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    /**
     * Owner/Principal can create exams
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin', 'principal']);
    }

    /**
     * Owner/Principal can lock exams
     */
    public function lock(User $user, Exam $exam): bool
    {
        return $user->hasRole(['super-admin', 'admin', 'principal'])
            && $exam->status->canLock();
    }

    /**
     * Owner/Principal can publish results
     */
    public function publish(User $user, Exam $exam): bool
    {
        return $user->hasRole(['super-admin', 'admin', 'principal'])
            && $exam->isReadyToPublish();
    }
}
