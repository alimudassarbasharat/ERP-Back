<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamMark;
use App\Models\GradingRule;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamResultsService
{
    /**
     * Generate results for an exam
     * Only runs if all marks are verified
     */
    public function generateResults(Exam $exam): array
    {
        if (!$exam->allMarksVerified()) {
            throw new \Exception('Cannot generate results: Not all marks are verified');
        }

        $results = [];
        $classes = $exam->examClasses()->with('class')->get();

        DB::transaction(function () use ($exam, $classes, &$results) {
            foreach ($classes as $examClass) {
                $classId = $examClass->class_id;
                $students = Student::where('current_class_id', $classId)
                    ->where('current_session_id', $exam->session_id)
                    ->where('status', 'active')
                    ->get();

                foreach ($students as $student) {
                    $result = $this->calculateStudentResult($exam, $student, $classId);
                    if ($result) {
                        $results[] = $result;
                    }
                }
            }

            // Calculate ranks
            $this->calculateRanks($exam);
        });

        return $results;
    }

    /**
     * Calculate result for a single student
     */
    protected function calculateStudentResult(Exam $exam, Student $student, int $classId): ?ExamResult
    {
        // Get all marks for this student in this exam
        $marks = ExamMark::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('class_id', $classId)
            ->where('status', '!=', \App\Enums\ExamMarkStatus::DRAFT)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        // Calculate totals
        $totalObtained = $marks->sum('marks_obtained');
        
        // Get total marks from papers
        $totalMarks = 0;
        foreach ($marks as $mark) {
            $paper = \App\Models\ExamPaper::where('exam_id', $mark->exam_id)
                ->where('class_id', $mark->class_id)
                ->where('subject_id', $mark->subject_id)
                ->first();
            if ($paper) {
                $totalMarks += $paper->total_marks;
            }
        }

        if ($totalMarks == 0) {
            return null;
        }

        $percentage = ($totalObtained / $totalMarks) * 100;

        // Get grade
        $gradeRule = GradingRule::getGradeForPercentage(
            $exam->school_id,
            $exam->session_id,
            $percentage
        );

        // Create snapshot with total marks per subject
        $snapshot = [
            'marks' => $marks->map(function ($mark) use ($exam, $classId) {
                $paper = \App\Models\ExamPaper::where('exam_id', $mark->exam_id)
                    ->where('class_id', $mark->class_id)
                    ->where('subject_id', $mark->subject_id)
                    ->first();
                
                return [
                    'subject_id' => $mark->subject_id,
                    'subject_name' => $mark->subject->name ?? null,
                    'marks_obtained' => $mark->marks_obtained,
                    'total_marks' => $paper ? $paper->total_marks : 0,
                    'is_absent' => $mark->is_absent,
                ];
            })->toArray(),
            'calculated_at' => now()->toIso8601String(),
        ];

        // Create or update result
        $result = ExamResult::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $exam->school_id,
                'total_obtained' => $totalObtained,
                'total_marks' => $totalMarks,
                'percentage' => round($percentage, 2),
                'grade' => $gradeRule?->grade,
                'result_snapshot_json' => $snapshot,
                'status' => 'provisional',
            ]
        );

        return $result;
    }

    /**
     * Calculate ranks for all students in each class
     */
    protected function calculateRanks(Exam $exam): void
    {
        $classes = $exam->examClasses()->with('class')->get();

        foreach ($classes as $examClass) {
            $classId = $examClass->class_id;

            // Get all results for this class, ordered by percentage desc
            $results = ExamResult::where('exam_id', $exam->id)
                ->whereHas('student', function ($query) use ($classId) {
                    $query->where('current_class_id', $classId);
                })
                ->orderBy('percentage', 'desc')
                ->orderBy('total_obtained', 'desc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                $result->update(['rank_in_class' => $rank]);
                $rank++;
            }
        }
    }

    /**
     * Check if exam is ready for results generation
     */
    public function isReadyForResults(Exam $exam): bool
    {
        return $exam->allMarksVerified() && $exam->status === \App\Enums\ExamStatus::LOCKED;
    }
}
