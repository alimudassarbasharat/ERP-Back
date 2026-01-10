<?php

namespace App\Services;

use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamPaperService
{
    /**
     * Submit paper for review
     */
    public function submitForReview(ExamPaper $paper): bool
    {
        if (!$paper->canEdit()) {
            return false;
        }

        DB::transaction(function () use ($paper) {
            $paper->submitForReview();

            // Trigger notification to supervisor
            $this->notifyPaperSubmitted($paper);
        });

        return true;
    }

    /**
     * Approve paper
     */
    public function approvePaper(ExamPaper $paper, int $reviewedBy, ?string $comment = null): bool
    {
        if (!$paper->status->canApprove()) {
            return false;
        }

        DB::transaction(function () use ($paper, $reviewedBy, $comment) {
            $paper->approve($reviewedBy, $comment);

            // Trigger notification to teacher
            $this->notifyPaperApproved($paper);
        });

        return true;
    }

    /**
     * Reject paper
     */
    public function rejectPaper(ExamPaper $paper, int $reviewedBy, string $comment): bool
    {
        if (!$paper->status->canApprove()) {
            return false;
        }

        DB::transaction(function () use ($paper, $reviewedBy, $comment) {
            $paper->reject($reviewedBy, $comment);

            // Trigger notification to teacher
            $this->notifyPaperRejected($paper);
        });

        return true;
    }

    /**
     * Lock paper
     */
    public function lockPaper(ExamPaper $paper): bool
    {
        if (!$paper->status->canLock()) {
            return false;
        }

        $paper->lock();
        return true;
    }

    /**
     * Add question to paper
     */
    public function addQuestion(ExamPaper $paper, array $questionData): ExamQuestion
    {
        if (!$paper->canEdit()) {
            throw new \Exception('Paper cannot be edited');
        }

        $question = ExamQuestion::create(array_merge($questionData, [
            'exam_paper_id' => $paper->id,
        ]));

        // Auto-update total marks
        $paper->updateTotalMarks();

        return $question;
    }

    /**
     * Update question
     */
    public function updateQuestion(ExamQuestion $question, array $questionData): bool
    {
        $paper = $question->examPaper;
        if (!$paper->canEdit()) {
            throw new \Exception('Paper cannot be edited');
        }

        $question->update($questionData);

        // Auto-update total marks
        $paper->updateTotalMarks();

        return true;
    }

    /**
     * Delete question
     */
    public function deleteQuestion(ExamQuestion $question): bool
    {
        $paper = $question->examPaper;
        if (!$paper->canEdit()) {
            throw new \Exception('Paper cannot be edited');
        }

        $question->delete();

        // Auto-update total marks
        $paper->updateTotalMarks();

        return true;
    }

    protected function notifyPaperSubmitted(ExamPaper $paper): void
    {
        // Create notification event for supervisor
        NotificationEvent::create([
            'school_id' => $paper->school_id,
            'type' => 'paper_submitted',
            'reference_type' => 'exam_paper',
            'reference_id' => $paper->id,
            'trigger' => 'on_submit',
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);
    }

    protected function notifyPaperApproved(ExamPaper $paper): void
    {
        if ($paper->created_by) {
            NotificationEvent::create([
                'school_id' => $paper->school_id,
                'type' => 'paper_approved',
                'reference_type' => 'exam_paper',
                'reference_id' => $paper->id,
                'trigger' => 'on_approve',
                'scheduled_at' => now(),
                'status' => 'pending',
            ]);
        }
    }

    protected function notifyPaperRejected(ExamPaper $paper): void
    {
        if ($paper->created_by) {
            NotificationEvent::create([
                'school_id' => $paper->school_id,
                'type' => 'paper_rejected',
                'reference_type' => 'exam_paper',
                'reference_id' => $paper->id,
                'trigger' => 'on_reject',
                'scheduled_at' => now(),
                'status' => 'pending',
            ]);
        }
    }
}
