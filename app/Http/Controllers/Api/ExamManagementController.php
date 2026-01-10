<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamMark;
use App\Services\ExamPaperService;
use App\Services\ExamMarksService;
use App\Jobs\GenerateExamResultsJob;
use App\Jobs\PublishExamResultsJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamManagementController extends Controller
{
    public function __construct(
        protected ExamPaperService $paperService,
        protected ExamMarksService $marksService
    ) {}

    /**
     * Get dashboard stats for Owner/Principal (enhanced with marks pending entry)
     */
    public function getDashboardStats(Request $request)
    {
        $schoolId = $request->user()->school_id ?? $request->input('school_id');

        // Check if tables exist first
        if (!Schema::hasTable('exam_papers') || !Schema::hasTable('exam_marks') || !Schema::hasTable('exams')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'papers_pending_approval' => 0,
                    'marks_pending_entry' => 0,
                    'marks_pending_verification' => 0,
                    'results_ready_to_publish' => 0,
                    'published_exams' => 0,
                ],
            ]);
        }

        try {
            // Cache for 2 minutes
            $cacheKey = "exam_stats_{$schoolId}";
            $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($schoolId) {
                $stats = [
                    'papers_pending_approval' => 0,
                    'marks_pending_entry' => 0,
                    'marks_pending_verification' => 0,
                    'results_ready_to_publish' => 0,
                    'published_exams' => 0,
                ];

                // Papers pending approval
                try {
                    if (Schema::hasTable('exam_papers') && Schema::hasColumn('exam_papers', 'status')) {
                        $stats['papers_pending_approval'] = ExamPaper::when($schoolId, function($q) use ($schoolId) {
                            $q->where('school_id', $schoolId);
                        }, function($q) {
                            $q->whereNull('school_id');
                        })
                            ->where('status', \App\Enums\ExamPaperStatus::SUBMITTED)
                            ->count();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to count papers pending approval: ' . $e->getMessage());
                }

                // Marks pending entry
                try {
                    if (Schema::hasTable('exam_marks') && Schema::hasColumn('exam_marks', 'status')) {
                        $stats['marks_pending_entry'] = ExamMark::when($schoolId, function($q) use ($schoolId) {
                            if (Schema::hasColumn('exam_marks', 'exam_id')) {
                                $q->whereHas('exam', function($query) use ($schoolId) {
                                    $query->where('school_id', $schoolId);
                                });
                            } else {
                                $q->where('school_id', $schoolId);
                            }
                        })
                            ->where('status', \App\Enums\ExamMarkStatus::DRAFT)
                            ->count();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to count marks pending entry: ' . $e->getMessage());
                }

                // Marks pending verification
                try {
                    if (Schema::hasTable('exam_marks') && Schema::hasColumn('exam_marks', 'status')) {
                        $stats['marks_pending_verification'] = ExamMark::when($schoolId, function($q) use ($schoolId) {
                            if (Schema::hasColumn('exam_marks', 'exam_id')) {
                                $q->whereHas('exam', function($query) use ($schoolId) {
                                    $query->where('school_id', $schoolId);
                                });
                            } else {
                                $q->where('school_id', $schoolId);
                            }
                        })
                            ->where('status', \App\Enums\ExamMarkStatus::SUBMITTED)
                            ->count();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to count marks pending verification: ' . $e->getMessage());
                }

                // Results ready to publish
                try {
                    if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'status')) {
                        $stats['results_ready_to_publish'] = Exam::when($schoolId, function($q) use ($schoolId) {
                            $q->where('school_id', $schoolId);
                        }, function($q) {
                            $q->whereNull('school_id');
                        })
                            ->where('status', \App\Enums\ExamStatus::LOCKED)
                            ->get()
                            ->filter(function($exam) {
                                try {
                                    return method_exists($exam, 'isReadyToPublish') && $exam->isReadyToPublish();
                                } catch (\Exception $e) {
                                    return false;
                                }
                            })
                            ->count();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to count results ready to publish: ' . $e->getMessage());
                }

                // Published exams
                try {
                    if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'status')) {
                        $stats['published_exams'] = Exam::when($schoolId, function($q) use ($schoolId) {
                            $q->where('school_id', $schoolId);
                        }, function($q) {
                            $q->whereNull('school_id');
                        })
                            ->where('status', \App\Enums\ExamStatus::PUBLISHED)
                            ->count();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to count published exams: ' . $e->getMessage());
                }

                return $stats;
            });
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Exam dashboard stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // If query fails, return zeros
            $stats = [
                'papers_pending_approval' => 0,
                'marks_pending_entry' => 0,
                'marks_pending_verification' => 0,
                'results_ready_to_publish' => 0,
                'published_exams' => 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
    
    /**
     * Get publish checklist for exam (shows what's blocking publication)
     */
    public function getPublishChecklist(Request $request, $examId)
    {
        $exam = Exam::with(['examPapers', 'examMarks', 'examClasses'])->findOrFail($examId);
        
        // Check datesheet (if required)
        $datesheet = \App\Models\ExamDatesheet::where('exam_id', $examId)
            ->where('school_id', $exam->school_id)
            ->first();
        
        $datesheetPublished = $datesheet && $datesheet->status === 'published';
        $datesheetRequired = $datesheet !== null; // If datesheet exists, it's required
        
        // Check papers
        $pendingPapers = $exam->examPapers()
            ->whereNotIn('status', [\App\Enums\ExamPaperStatus::APPROVED, \App\Enums\ExamPaperStatus::LOCKED])
            ->count();
        
        // Check marks
        $pendingMarks = $exam->examMarks()
            ->whereNotIn('status', [\App\Enums\ExamMarkStatus::VERIFIED, \App\Enums\ExamMarkStatus::LOCKED])
            ->count();
        
        // Check missing students (students in exam classes without marks)
        $missingStudents = 0;
        $examClasses = $exam->examClasses()->pluck('class_id')->toArray();
        if (!empty($examClasses)) {
            $totalStudents = \App\Models\Student::whereIn('current_class_id', $examClasses)
                ->where('current_session_id', $exam->session_id)
                ->where('status', 'active')
                ->count();
            
            $studentsWithMarks = $exam->examMarks()
                ->whereIn('class_id', $examClasses)
                ->distinct('student_id')
                ->count('student_id');
            
            $missingStudents = max(0, $totalStudents - $studentsWithMarks);
        }
        
        // Check invalid marks (marks exceeding paper total)
        $invalidMarks = 0;
        $marks = $exam->examMarks()
            ->where('status', '!=', \App\Enums\ExamMarkStatus::DRAFT)
            ->with(['paper'])
            ->get();
        
        foreach ($marks as $mark) {
            if ($mark->paper && $mark->marks_obtained > $mark->paper->total_marks) {
                $invalidMarks++;
            }
        }
        
        $checklist = [
            'datesheet_published' => [
                'status' => !$datesheetRequired || $datesheetPublished,
                'message' => !$datesheetRequired 
                    ? 'No datesheet required' 
                    : ($datesheetPublished 
                        ? 'Datesheet is published' 
                        : 'Datesheet must be published before results')
            ],
            'all_papers_approved' => [
                'status' => $pendingPapers === 0,
                'message' => $pendingPapers === 0 
                    ? 'All papers are approved' 
                    : "{$pendingPapers} paper(s) need approval"
            ],
            'all_marks_verified' => [
                'status' => $pendingMarks === 0,
                'message' => $pendingMarks === 0
                    ? 'All marks are verified'
                    : "{$pendingMarks} mark(s) need verification"
            ],
            'no_missing_students' => [
                'status' => $missingStudents === 0,
                'message' => $missingStudents === 0
                    ? 'All students have marks'
                    : "{$missingStudents} student(s) are missing marks"
            ],
            'no_invalid_marks' => [
                'status' => $invalidMarks === 0,
                'message' => $invalidMarks === 0
                    ? 'All marks are valid'
                    : "{$invalidMarks} mark(s) exceed paper total"
            ]
        ];
        
        $allPassed = collect($checklist)->every(fn($item) => $item['status']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name ?? $exam->term,
                'ready_to_publish' => $allPassed,
                'checklist' => $checklist
            ]
        ]);
    }

    /**
     * Lock exam (Owner/Principal only)
     */
    public function lockExam(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);

        if (!$exam->status->canLock()) {
            return response()->json([
                'success' => false,
                'message' => 'Exam cannot be locked in current status',
            ], 400);
        }

        DB::transaction(function () use ($exam) {
            $exam->lock();

            // Lock all approved papers
            $exam->examPapers()->where('status', \App\Enums\ExamPaperStatus::APPROVED)->each(function ($paper) {
                $paper->lock();
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Exam locked successfully',
        ]);
    }

    /**
     * Publish results (Owner/Principal only - ONE CLICK)
     */
    public function publishResults(Request $request, $examId)
    {
        $validator = Validator::make($request->all(), [
            'confirm' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm to publish results',
            ], 422);
        }

        $exam = Exam::findOrFail($examId);

        if (!$exam->isReadyToPublish()) {
            return response()->json([
                'success' => false,
                'message' => 'Exam is not ready to publish. Ensure all papers are approved and marks are verified.',
            ], 400);
        }

        // Check if results are generated
        if ($exam->examResults()->where('status', 'provisional')->count() === 0) {
            // Generate results first
            GenerateExamResultsJob::dispatch($exam->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Results generation started. Please wait and try publishing again.',
            ], 202);
        }

        // Dispatch publish job (async)
        PublishExamResultsJob::dispatch($exam->id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Results publishing job dispatched',
        ], 202);
    }

    /**
     * Generate results for exam (async)
     */
    public function generateResults(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);

        if (!$exam->allMarksVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot generate results: Not all marks are verified',
            ], 400);
        }

        // Dispatch job (async)
        GenerateExamResultsJob::dispatch($exam->id);

        return response()->json([
            'success' => true,
            'message' => 'Results generation job dispatched',
        ], 202);
    }

    /**
     * Download marksheets (bulk)
     */
    public function downloadMarksheets(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);
        $results = $exam->examResults()
            ->where('status', 'published')
            ->whereNotNull('marksheet_pdf_path')
            ->get();

        // Return list of PDF paths (frontend can download individually or create zip)
        return response()->json([
            'success' => true,
            'data' => $results->map(fn($r) => [
                'student_id' => $r->student_id,
                'student_name' => $r->student->name ?? 'N/A',
                'pdf_path' => $r->marksheet_pdf_path,
            ]),
        ]);
    }
}
