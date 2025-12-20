<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResultSheet;

class ResultSheetController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'exam_id' => 'required|integer|exists:exams,id',
            'total_mark_obtains' => 'required|integer',
            'total_marks' => 'required|integer',
            'percentage' => 'nullable|string',
            'grade' => 'nullable|string',
            'position' => 'nullable|string',
        ]);

        // Calculate percentage if not provided
        if (!isset($validated['percentage'])) {
            $percentage = ($validated['total_mark_obtains'] / $validated['total_marks']) * 100;
            $validated['percentage'] = number_format($percentage, 2) . '%';
        }

        // Calculate grade based on percentage
        $percentageValue = floatval(str_replace('%', '', $validated['percentage']));
        $validated['grade'] = ResultSheet::calculateGrade($percentageValue);
        $validated['status'] = $percentageValue > 33 ? 'Pass' : 'Fail';

        $result = ResultSheet::create($validated);

        return response()->json(['status' => true, 'result' => $result]);
    }

    public function index(Request $request)
    {
        $query = ResultSheet::with(['student', 'exam']);

        // Apply filters if provided
        if ($request->has('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->has('section_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        if ($request->has('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        $results = $query->get();
        return response()->json(['status' => true, 'result' => $results]);
    }
} 