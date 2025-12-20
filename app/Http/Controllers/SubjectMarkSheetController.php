<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubjectMarkSheet;

class SubjectMarkSheetController extends Controller
{
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'exam_id' => 'required|integer|exists:exams,id',
            'marks' => 'required|array|min:1',
            'marks.*.subject_id' => 'required|integer|exists:subjects,id',
            'marks.*.mark_obtained' => 'required|integer',
            'marks.*.max_marks' => 'required|integer',
        ]);

        $student_id = $validated['student_id'];
        $exam_id = $validated['exam_id'];
        $marks = $validated['marks'];

        foreach ($marks as $mark) {
            SubjectMarkSheet::updateOrCreate(
                [
                    'student_id' => $student_id,
                    'subject_id' => $mark['subject_id'],
                    'exam_id' => $exam_id,
                    'created_by' => auth()->user()->id,
                    'merchant_id' => auth()->user()->merchant_id,
                ],
                [
                    'mark_obtained' => $mark['mark_obtained'],
                    'max_marks' => $mark['max_marks'],
                    'grade' => $mark['grade'] ?? null,
                    'remarks' => $mark['remarks'] ?? null,
                ]
            );
        }

        return response()->json(['status' => true, 'message' => 'Marks saved successfully']);
    }
} 