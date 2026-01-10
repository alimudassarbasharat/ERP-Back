<?php

namespace App\Services;

use App\Models\ExamMark;
use App\Models\Exam;
use App\Models\Student;
use App\Models\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ExamMarksService
{
    /**
     * Submit marks for verification
     */
    public function submitForVerification(ExamMark $mark): bool
    {
        if (!$mark->status->canSubmit()) {
            return false;
        }

        DB::transaction(function () use ($mark) {
            $mark->submitForVerification();

            // Trigger notification to supervisor
            $this->notifyMarksSubmitted($mark);
        });

        return true;
    }

    /**
     * Submit bulk marks for verification
     */
    public function submitBulkForVerification(array $markIds): int
    {
        $count = 0;
        
        DB::transaction(function () use ($markIds, &$count) {
            $marks = ExamMark::whereIn('id', $markIds)
                ->where('status', \App\Enums\ExamMarkStatus::DRAFT)
                ->get();

            foreach ($marks as $mark) {
                if ($mark->submitForVerification()) {
                    $count++;
                    $this->notifyMarksSubmitted($mark);
                }
            }
        });

        return $count;
    }

    /**
     * Verify marks
     */
    public function verifyMarks(ExamMark $mark, int $verifiedBy): bool
    {
        if (!$mark->status->canVerify()) {
            return false;
        }

        $mark->verify($verifiedBy);
        return true;
    }

    /**
     * Verify bulk marks
     */
    public function verifyBulkMarks(array $markIds, int $verifiedBy): int
    {
        $count = 0;

        DB::transaction(function () use ($markIds, $verifiedBy, &$count) {
            $marks = ExamMark::whereIn('id', $markIds)
                ->where('status', \App\Enums\ExamMarkStatus::SUBMITTED)
                ->get();

            foreach ($marks as $mark) {
                if ($mark->verify($verifiedBy)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Lock marks
     */
    public function lockMarks(ExamMark $mark): bool
    {
        if (!$mark->status->canLock()) {
            return false;
        }

        $mark->lock();
        return true;
    }

    /**
     * Lock all marks for an exam
     */
    public function lockAllMarksForExam(int $examId): int
    {
        $count = 0;

        DB::transaction(function () use ($examId, &$count) {
            $marks = ExamMark::where('exam_id', $examId)
                ->where('status', \App\Enums\ExamMarkStatus::VERIFIED)
                ->get();

            foreach ($marks as $mark) {
                if ($mark->lock()) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Fetch students for marks entry (multi-class/multi-subject support)
     */
    public function fetchStudents(int $examId, array $classIds, ?array $sectionIds = null): Collection
    {
        $query = Student::whereIn('current_class_id', $classIds)
            ->where('status', 'active')
            ->with(['class', 'section']);

        if ($sectionIds && !empty($sectionIds)) {
            $query->whereIn('section_id', $sectionIds);
        }

        // Get exam to check session
        $exam = Exam::findOrFail($examId);
        if ($exam->session_id) {
            $query->where('current_session_id', $exam->session_id);
        }

        return $query->get();
    }

    /**
     * Fetch subjects for marks entry (multi-class support)
     */
    public function fetchSubjects(int $examId, array $classIds): Collection
    {
        $exam = Exam::with('subjects')->findOrFail($examId);
        
        // Get subjects linked to exam
        $examSubjectIds = $exam->subjects->pluck('id')->toArray();
        
        // Get subjects for these classes
        return \App\Models\Subject::whereHas('classes', function ($q) use ($classIds) {
                $q->whereIn('classes.id', $classIds);
            })
            ->whereIn('id', $examSubjectIds)
            ->with('classes')
            ->get();
    }

    /**
     * Save draft marks (bulk, multi-class/multi-subject)
     */
    public function saveDraftMarks(int $examId, array $marksData, int $teacherId): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        DB::transaction(function () use ($examId, $marksData, $teacherId, &$results) {
            foreach ($marksData as $markData) {
                try {
                    $mark = ExamMark::updateOrCreate(
                        [
                            'exam_id' => $examId,
                            'class_id' => $markData['class_id'],
                            'subject_id' => $markData['subject_id'],
                            'student_id' => $markData['student_id'],
                        ],
                        [
                            'marks_obtained' => $markData['marks_obtained'] ?? 0,
                            'is_absent' => $markData['is_absent'] ?? false,
                            'teacher_id' => $teacherId,
                            'status' => \App\Enums\ExamMarkStatus::DRAFT,
                        ]
                    );

                    if ($mark->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'student_id' => $markData['student_id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Submit marks for verification (bulk)
     */
    public function submitMarks(int $examId, array $classIds, array $subjectIds, ?array $sectionIds = null): int
    {
        $query = ExamMark::where('exam_id', $examId)
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->where('status', \App\Enums\ExamMarkStatus::DRAFT);

        if ($sectionIds && !empty($sectionIds)) {
            $query->whereHas('student', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        $marks = $query->get();
        $count = 0;

        DB::transaction(function () use ($marks, &$count) {
            foreach ($marks as $mark) {
                if ($mark->submitForVerification()) {
                    $count++;
                    $this->notifyMarksSubmitted($mark);
                }
            }
        });

        return $count;
    }

    /**
     * Verify marks (bulk, multi-class/multi-subject)
     */
    public function verifyMarks(int $examId, array $classIds, array $subjectIds, int $verifiedBy, ?array $sectionIds = null): int
    {
        $query = ExamMark::where('exam_id', $examId)
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->where('status', \App\Enums\ExamMarkStatus::SUBMITTED);

        if ($sectionIds && !empty($sectionIds)) {
            $query->whereHas('student', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        $marks = $query->get();
        $count = 0;

        DB::transaction(function () use ($marks, $verifiedBy, &$count) {
            foreach ($marks as $mark) {
                if ($mark->verify($verifiedBy)) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Lock marks (bulk, multi-class/multi-subject)
     */
    public function lockMarks(int $examId, array $classIds, array $subjectIds, ?array $sectionIds = null): int
    {
        $query = ExamMark::where('exam_id', $examId)
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->where('status', \App\Enums\ExamMarkStatus::VERIFIED);

        if ($sectionIds && !empty($sectionIds)) {
            $query->whereHas('student', function ($q) use ($sectionIds) {
                $q->whereIn('section_id', $sectionIds);
            });
        }

        $marks = $query->get();
        $count = 0;

        DB::transaction(function () use ($marks, &$count) {
            foreach ($marks as $mark) {
                if ($mark->lock()) {
                    $count++;
                }
            }
        });

        return $count;
    }

    protected function notifyMarksSubmitted(ExamMark $mark): void
    {
        NotificationEvent::create([
            'school_id' => $mark->exam->school_id ?? null,
            'type' => 'marks_submitted',
            'reference_type' => 'exam_mark',
            'reference_id' => $mark->id,
            'trigger' => 'on_submit',
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);
    }
}
