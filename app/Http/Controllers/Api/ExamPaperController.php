<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Services\ExamPaperService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamPaperController extends Controller
{
    public function __construct(
        protected ExamPaperService $paperService
    ) {}

    /**
     * List papers with filters
     */
    public function index(Request $request)
    {
        // Check if table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('exam_papers')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'last_page' => 1
                ]
            ]);
        }

        $schoolId = $request->user()->school_id;
        $query = ExamPaper::where('school_id', $schoolId)
            ->with(['exam', 'class', 'subject', 'createdBy', 'reviewedBy']);

        // Filters
        if ($request->has('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $papers = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $papers
        ]);
    }

    /**
     * Create new paper
     */
    public function store(Request $request)
    {
        if (!Schema::hasTable('exam_papers')) {
            return response()->json([
                'success' => false,
                'message' => 'Database tables not set up. Please run migrations: php artisan migrate'
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:1000',
            'total_marks' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $schoolId = $request->user()->school_id;

        $paper = ExamPaper::create([
            'exam_id' => $request->exam_id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'school_id' => $schoolId,
            'title' => $request->title,
            'instructions' => $request->instructions,
            'total_marks' => $request->total_marks ?? 0,
            'duration_minutes' => $request->duration_minutes,
            'status' => \App\Enums\ExamPaperStatus::DRAFT,
            'paper_version' => 1,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $paper->load(['exam', 'class', 'subject'])
        ]);
    }

    /**
     * Get paper with questions
     */
    public function show(Request $request, $id)
    {
        if (!Schema::hasTable('exam_papers')) {
            return response()->json([
                'success' => false,
                'message' => 'Database tables not set up. Please run migrations: php artisan migrate'
            ], 503);
        }

        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)
            ->with(['exam', 'class', 'subject', 'questions', 'createdBy', 'reviewedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $paper
        ]);
    }

    /**
     * Update paper
     */
    public function update(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$paper->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be edited in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'instructions' => 'nullable|string|max:1000',
            'total_marks' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $paper->update($request->only(['title', 'instructions', 'total_marks', 'duration_minutes']));

        return response()->json([
            'success' => true,
            'data' => $paper->load(['exam', 'class', 'subject'])
        ]);
    }

    /**
     * Add question to paper
     */
    public function addQuestion(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$paper->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be edited in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'section_name' => 'required|string|max:255',
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,short,long',
            'marks' => 'required|integer|min:1',
            'options_json' => 'nullable|json',
            'answer_key' => 'nullable|string',
            'order_no' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $maxOrder = ExamQuestion::where('exam_paper_id', $paper->id)->max('order_no') ?? 0;

        $question = $this->paperService->addQuestion($paper, [
            'section_name' => $request->section_name,
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'marks' => $request->marks,
            'options_json' => $request->options_json,
            'answer_key' => $request->answer_key,
            'order_no' => $request->order_no ?? ($maxOrder + 1),
        ]);

        return response()->json([
            'success' => true,
            'data' => $question
        ]);
    }

    /**
     * Update question
     */
    public function updateQuestion(Request $request, $id, $questionId)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$paper->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be edited in current status'
            ], 400);
        }

        $question = ExamQuestion::where('exam_paper_id', $paper->id)->findOrFail($questionId);

        $validator = Validator::make($request->all(), [
            'section_name' => 'sometimes|string|max:255',
            'question_text' => 'sometimes|string',
            'question_type' => 'sometimes|in:mcq,short,long',
            'marks' => 'sometimes|integer|min:1',
            'options_json' => 'nullable|json',
            'answer_key' => 'nullable|string',
            'order_no' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $question->update($request->only([
            'section_name', 'question_text', 'question_type', 'marks',
            'options_json', 'answer_key', 'order_no'
        ]));

        $paper->updateTotalMarks();

        return response()->json([
            'success' => true,
            'data' => $question
        ]);
    }

    /**
     * Delete question
     */
    public function deleteQuestion(Request $request, $id, $questionId)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$paper->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be edited in current status'
            ], 400);
        }

        $question = ExamQuestion::where('exam_paper_id', $paper->id)->findOrFail($questionId);
        $question->delete();

        $paper->updateTotalMarks();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Submit paper for review
     */
    public function submit(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$this->paperService->submitForReview($paper)) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be submitted in current status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $paper->fresh()->load(['exam', 'class', 'subject'])
        ]);
    }

    /**
     * Approve paper
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$this->paperService->approvePaper($paper, $request->user()->id, $request->comment)) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be approved in current status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $paper->fresh()->load(['exam', 'class', 'subject', 'reviewedBy'])
        ]);
    }

    /**
     * Reject paper
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$this->paperService->rejectPaper($paper, $request->user()->id, $request->comment)) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be rejected in current status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $paper->fresh()->load(['exam', 'class', 'subject', 'reviewedBy'])
        ]);
    }

    /**
     * Lock paper
     */
    public function lock(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $paper = ExamPaper::where('school_id', $schoolId)->findOrFail($id);

        if (!$this->paperService->lockPaper($paper)) {
            return response()->json([
                'success' => false,
                'message' => 'Paper cannot be locked in current status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $paper->fresh()->load(['exam', 'class', 'subject'])
        ]);
    }
}
