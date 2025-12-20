<?php

namespace App\Http\Controllers\Subject;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    // Bulk store subjects
    public function bulkStore(\App\Http\Requests\Subject\BulkStoreSubjectsRequest $request)
    {
        $subjects = [];
        foreach ($request->validated()['names'] as $name) {
            $subjects[] = Subject::create(['name' => $name]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subjects added successfully',
            'result' => $subjects
        ]);
    }

    // CRUD methods
    public function index(Request $request)
    {
        try {
            $query = Subject::query();
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }
            
            // Sorting
            $orderBy = $request->get('sort_by', 'name');
            $direction = $request->get('sort_direction', 'asc');
            $allowed = ['id', 'name', 'created_at'];
            if (!in_array($orderBy, $allowed)) {
                $orderBy = 'name';
            }
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $subjects = $query->paginate($perPage);

            return response()->json([
                'status' => true, 
                'message' => 'Subjects fetched successfully', 
                'result' => [
                    'data' => $subjects->items(),
                    'current_page' => $subjects->currentPage(),
                    'last_page' => $subjects->lastPage(),
                    'per_page' => $subjects->perPage(),
                    'total' => $subjects->total(),
                    'from' => $subjects->firstItem(),
                    'to' => $subjects->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(\App\Http\Requests\Subject\StoreSubjectRequest $request)
    {
        $subject = Subject::create(['name' => $request->validated()['name']]);
        return response()->json(['status' => true, 'result' => $subject]);
    }

    public function show($id)
    {
        $subject = Subject::findOrFail($id);
        return response()->json(['status' => true, 'result' => $subject]);
    }

    public function update(\App\Http\Requests\Subject\UpdateSubjectRequest $request, $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['name' => $request->validated()['name']]);
        return response()->json(['status' => true, 'result' => $subject]);
    }

    public function delete($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();
        return response()->json(['status' => true, 'message' => 'Subject deleted']);
    }

    public function classes($id)
    {
        $subject = Subject::with('classes')->findOrFail($id);
        return response()->json([
            'status' => true,
            'result' => $subject->classes
        ]);
    }
} 