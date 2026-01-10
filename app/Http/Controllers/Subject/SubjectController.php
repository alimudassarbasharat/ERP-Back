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
            $query = Subject::with('classes');
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }
            
            // Filter by class_id
            if ($request->has('class_id') && !empty($request->class_id)) {
                $query->whereHas('classes', function($q) use ($request) {
                    $q->where('classes.id', $request->class_id);
                });
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
            
            // Transform data to include class count
            $transformedData = $subjects->items();
            foreach ($transformedData as $subject) {
                $subject->classes_count = $subject->classes->count();
            }

            return response()->json([
                'status' => true, 
                'message' => 'Subjects fetched successfully', 
                'result' => [
                    'data' => $transformedData,
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
        try {
            $subject = Subject::findOrFail($id);
            $data = $request->validated();
            
            // Preserve merchant_id
            if (!isset($data['merchant_id'])) {
                $data['merchant_id'] = $subject->merchant_id ?: auth()->user()->merchant_id;
            }
            
            $subject->update($data);
            
            return response()->json([
                'status' => true,
                'message' => 'Subject updated successfully',
                'result' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update subject',
                'error' => $e->getMessage()
            ], 500);
        }
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

