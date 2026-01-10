<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExamTerm;
use Illuminate\Support\Facades\Validator;

class ExamTermController extends Controller
{
    /**
     * List terms with filters
     */
    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $query = ExamTerm::where('school_id', $schoolId);

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $terms = $query->orderBy('order')->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $terms
        ]);
    }

    /**
     * Get active terms for session
     */
    public function active(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $sessionId = $request->input('session_id');

        $terms = ExamTerm::where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($sessionId, function($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            })
            ->orderBy('order')
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $terms
        ]);
    }

    /**
     * Create new term
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|exists:sessions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'sometimes|in:active,inactive',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $schoolId = $request->user()->school_id;

        // Check for duplicate name in same session
        $exists = ExamTerm::where('school_id', $schoolId)
            ->where('session_id', $request->session_id)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Term with this name already exists for this session'
            ], 422);
        }

        $term = ExamTerm::create([
            'school_id' => $schoolId,
            'session_id' => $request->session_id,
            'name' => $request->name,
            'code' => $request->code,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'active',
            'order' => $request->order ?? 0,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'data' => $term->load(['session'])
        ]);
    }

    /**
     * Get term
     */
    public function show(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $term = ExamTerm::where('school_id', $schoolId)
            ->with(['session', 'exams'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $term
        ]);
    }

    /**
     * Update term
     */
    public function update(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $term = ExamTerm::where('school_id', $schoolId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:active,inactive',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Check for duplicate name if name is being changed
        if ($request->has('name') && $request->name !== $term->name) {
            $exists = ExamTerm::where('school_id', $schoolId)
                ->where('session_id', $term->session_id)
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Term with this name already exists for this session'
                ], 422);
            }
        }

        $term->update($request->only([
            'name', 'code', 'start_date', 'end_date', 'status', 'order', 'description'
        ]));

        return response()->json([
            'success' => true,
            'data' => $term->fresh()->load(['session'])
        ]);
    }

    /**
     * Delete term
     */
    public function destroy(Request $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $term = ExamTerm::where('school_id', $schoolId)->findOrFail($id);

        // Check if term has exams
        if ($term->exams()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete term: It has associated exams. Please delete or reassign exams first.'
            ], 400);
        }

        $term->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully'
        ]);
    }
}
