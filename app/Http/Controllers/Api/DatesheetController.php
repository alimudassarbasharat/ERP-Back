<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExamDatesheet;
use App\Models\ExamDatesheetEntry;
use App\Models\Exam;
use App\Services\DatesheetConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DatesheetController extends Controller
{
    public function __construct(
        protected DatesheetConflictService $conflictService
    ) {}

    /**
     * Get datesheet for exam
     */
    public function getDatesheet(Request $request, $examId)
    {
        $schoolId = $request->user()->school_id;
        
        $datesheet = ExamDatesheet::where('exam_id', $examId)
            ->where('school_id', $schoolId)
            ->with(['entries.class', 'entries.section', 'entries.subject', 'entries.paper', 'entries.supervisor', 'entries.invigilator'])
            ->first();

        if (!$datesheet) {
            return response()->json([
                'success' => false,
                'message' => 'Datesheet not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $datesheet
        ]);
    }

    /**
     * Create or update datesheet
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $schoolId = $request->user()->school_id;
        $examId = $request->input('exam_id');

        // Check if datesheet exists
        $datesheet = ExamDatesheet::where('exam_id', $examId)
            ->where('school_id', $schoolId)
            ->first();

        if (!$datesheet) {
            $datesheet = ExamDatesheet::create([
                'exam_id' => $examId,
                'school_id' => $schoolId,
                'status' => 'draft',
                'created_by' => $request->user()->id,
                'notes' => $request->input('notes'),
            ]);
        } else {
            $datesheet->update([
                'notes' => $request->input('notes'),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $datesheet->load(['entries.class', 'entries.section', 'entries.subject'])
        ]);
    }

    /**
     * Add entry to datesheet
     */
    public function addEntry(Request $request, $datesheetId)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_id' => 'nullable|string|max:100',
            'room_name' => 'nullable|string|max:255',
            'supervisor_id' => 'nullable|exists:users,id',
            'invigilator_id' => 'nullable|exists:users,id',
            'paper_id' => 'nullable|exists:exam_papers,id',
            'total_marks' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $datesheet = ExamDatesheet::findOrFail($datesheetId);

        if ($datesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify published datesheet'
            ], 400);
        }

        $entry = ExamDatesheetEntry::create([
            'datesheet_id' => $datesheetId,
            'exam_id' => $datesheet->exam_id,
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'subject_id' => $request->input('subject_id'),
            'exam_date' => $request->input('exam_date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'room_id' => $request->input('room_id'),
            'room_name' => $request->input('room_name'),
            'supervisor_id' => $request->input('supervisor_id'),
            'invigilator_id' => $request->input('invigilator_id'),
            'paper_id' => $request->input('paper_id'),
            'total_marks' => $request->input('total_marks'),
            'instructions' => $request->input('instructions'),
        ]);

        // Check conflicts
        $this->conflictService->updateConflictFlags($datesheetId);

        return response()->json([
            'success' => true,
            'data' => $entry->load(['class', 'section', 'subject', 'paper', 'supervisor', 'invigilator'])
        ]);
    }

    /**
     * Update entry
     */
    public function updateEntry(Request $request, $entryId)
    {
        $entry = ExamDatesheetEntry::with('datesheet')->findOrFail($entryId);

        if ($entry->datesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify published datesheet'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'exam_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'room_id' => 'nullable|string|max:100',
            'room_name' => 'nullable|string|max:255',
            'supervisor_id' => 'nullable|exists:users,id',
            'invigilator_id' => 'nullable|exists:users,id',
            'paper_id' => 'nullable|exists:exam_papers,id',
            'total_marks' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $entry->update($request->only([
            'exam_date', 'start_time', 'end_time', 'room_id', 'room_name',
            'supervisor_id', 'invigilator_id', 'paper_id', 'total_marks', 'instructions'
        ]));

        // Recheck conflicts
        $this->conflictService->updateConflictFlags($entry->datesheet_id);

        return response()->json([
            'success' => true,
            'data' => $entry->load(['class', 'section', 'subject', 'paper', 'supervisor', 'invigilator'])
        ]);
    }

    /**
     * Delete entry
     */
    public function deleteEntry(Request $request, $entryId)
    {
        $entry = ExamDatesheetEntry::with('datesheet')->findOrFail($entryId);

        if ($entry->datesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify published datesheet'
            ], 400);
        }

        $datesheetId = $entry->datesheet_id;
        $entry->delete();

        // Recheck conflicts
        $this->conflictService->updateConflictFlags($datesheetId);

        return response()->json([
            'success' => true,
            'message' => 'Entry deleted successfully'
        ]);
    }

    /**
     * Get conflicts for datesheet
     */
    public function getConflicts(Request $request, $datesheetId)
    {
        $conflicts = $this->conflictService->detectConflicts($datesheetId);

        return response()->json([
            'success' => true,
            'data' => [
                'conflict_count' => count($conflicts),
                'conflicts' => $conflicts
            ]
        ]);
    }

    /**
     * Publish datesheet (only if no conflicts)
     */
    public function publish(Request $request, $datesheetId)
    {
        $datesheet = ExamDatesheet::findOrFail($datesheetId);

        if ($datesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Datesheet is already published or archived'
            ], 400);
        }

        // Check conflicts
        $conflicts = $this->conflictService->detectConflicts($datesheetId);
        
        if (count($conflicts) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot publish datesheet with conflicts',
                'conflict_count' => count($conflicts),
                'conflicts' => $conflicts
            ], 400);
        }

        $datesheet->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Datesheet published successfully',
            'data' => $datesheet
        ]);
    }
}
