<?php

namespace App\Http\Controllers\Exam;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::all();
        return response()->json(['status' => true, 'result' => $exams]);
    }

    public function store(StoreExamRequest $request)
    {
        $validated = $request->validated();
        $validated['merchant_id'] = $request->merchant_id;
        $validated['created_by'] = Auth::id();
        $exam = Exam::create($validated);
        return response()->json(['status' => true, 'message' => 'Exam created successfully', 'result' => $exam]);
    }

    public function show($id)
    {
        $exam = Exam::findOrFail($id);
        return response()->json(['status' => true, 'result' => $exam]);
    }

    public function update(\App\Http\Requests\Exam\UpdateExamRequest $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $validated = $request->validated();
        $validated['merchant_id'] = $request->merchant_id;
        $validated['created_by'] = Auth::id();
        $exam->update($validated);
        return response()->json(['status' => true, 'message' => 'Exam updated successfully', 'result' => $exam]);
    }

    public function delete($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        return response()->json(['status' => true, 'message' => 'Exam deleted successfully']);
    }

    public function subjects($id)
    {
        $exam = Exam::findOrFail($id);
        $subjects = $exam->subjects;
        return response()->json(['status' => true, 'result' => $subjects]);
    }
}


