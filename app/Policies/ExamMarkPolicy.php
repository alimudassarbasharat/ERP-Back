<?php

namespace App\Policies;

use App\Models\ExamMark;
use App\Models\User;

class ExamMarkPolicy
{
    /**
     * Teachers can create/update marks
     */
    public function update(User $user, ExamMark $examMark): bool
    {
        // Owner/Principal can always update
        if ($user->hasRole(['super-admin', 'admin', 'principal'])) {
            return true;
        }

        // Teachers can update if editable
        if ($user->hasRole('teacher')) {
            return $examMark->canEdit();
        }

        return false;
    }

    /**
     * Supervisors can verify
     */
    public function verify(User $user, ExamMark $examMark): bool
    {
        return $user->hasRole(['supervisor', 'hod', 'super-admin', 'admin', 'principal'])
            && $examMark->status->canVerify();
    }

    /**
     * Teachers can submit for verification
     */
    public function submit(User $user, ExamMark $examMark): bool
    {
        return $user->hasRole(['teacher', 'super-admin', 'admin'])
            && $examMark->status->canSubmit();
    }
}
