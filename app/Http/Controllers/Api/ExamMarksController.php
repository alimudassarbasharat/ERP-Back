<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExamMarksService;
use Illuminate\Support\Facades\Validator;

class ExamMarksController extends Controller
{
    public function __construct(
        protected ExamMarksService $marksService
    ) {}

    /**
     * Fetch students for marks entry
     */
    public function fetchStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $students = $this->marksService->fetchStudents(
            $request->input('exam_id'),
            $request->input('class_ids'),
            $request->input('section_ids')
        );

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Fetch subjects for marks entry
     */
    public function fetchSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $subjects = $this->marksService->fetchSubjects(
            $request->input('exam_id'),
            $request->input('class_ids')
        );

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Save draft marks
     */
    public function saveDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'marks' => 'required|array|min:1',
            'marks.*.class_id' => 'required|exists:classes,id',
            'marks.*.subject_id' => 'required|exists:subjects,id',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks_obtained' => 'required|numeric|min:0',
            'marks.*.is_absent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $results = $this->marksService->saveDraftMarks(
            $request->input('exam_id'),
            $request->input('marks'),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Submit marks for verification
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $count = $this->marksService->submitMarks(
            $request->input('exam_id'),
            $request->input('class_ids'),
            $request->input('subject_ids'),
            $request->input('section_ids')
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} mark(s) submitted for verification",
            'data' => ['count' => $count]
        ]);
    }

    /**
     * Verify marks
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $count = $this->marksService->verifyMarks(
            $request->input('exam_id'),
            $request->input('class_ids'),
            $request->input('subject_ids'),
            $request->user()->id,
            $request->input('section_ids')
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} mark(s) verified",
            'data' => ['count' => $count]
        ]);
    }

    /**
     * Lock marks
     */
    public function lock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $count = $this->marksService->lockMarks(
            $request->input('exam_id'),
            $request->input('class_ids'),
            $request->input('subject_ids'),
            $request->input('section_ids')
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} mark(s) locked",
            'data' => ['count' => $count]
        ]);
    }
}
