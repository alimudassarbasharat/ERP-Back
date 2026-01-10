<?php

namespace App\Policies;

use App\Models\ExamPaper;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamPaperPolicy
{
    /**
     * Teachers can create papers
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['teacher', 'super-admin', 'admin']);
    }

    /**
     * Teachers can update their own papers (if draft/rejected)
     */
    public function update(User $user, ExamPaper $examPaper): bool
    {
        // Owner/Principal can always update
        if ($user->hasRole(['super-admin', 'admin', 'principal'])) {
            return true;
        }

        // Teachers can only update their own papers if editable
        if ($user->hasRole('teacher') && $examPaper->created_by === $user->id) {
            return $examPaper->canEdit();
        }

        return false;
    }

    /**
     * Supervisors can approve/reject
     */
    public function approve(User $user, ExamPaper $examPaper): bool
    {
        return $user->hasRole(['supervisor', 'hod', 'super-admin', 'admin', 'principal'])
            && $examPaper->status->canApprove();
    }

    /**
     * Owner/Principal can lock
     */
    public function lock(User $user, ExamPaper $examPaper): bool
    {
        return $user->hasRole(['super-admin', 'admin', 'principal'])
            && $examPaper->status->canLock();
    }

    /**
     * Teachers can submit for review
     */
    public function submit(User $user, ExamPaper $examPaper): bool
    {
        return $user->hasRole(['teacher', 'super-admin', 'admin'])
            && $examPaper->created_by === $user->id
            && $examPaper->status->canSubmit();
    }
}
