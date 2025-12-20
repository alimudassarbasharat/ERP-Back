<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Http\Requests\Class\StoreClassRequest;
use App\Http\Requests\Class\UpdateClassRequest;
use Illuminate\Http\Request;
use App\Models\Section;

class ClassController extends Controller
{
    /**
     * Display a listing of the classes.
     */
    public function index(Request $request)
    {
        try {
            $query = Classes::with('subjects');
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Section filter
            if ($request->has('section_id') && !empty($request->section_id)) {
                $query->where('section_id', $request->section_id);
            }
            
            // Sorting
            $orderBy = $request->get('sort_by', 'name');
            $direction = $request->get('sort_direction', 'asc');
            $allowed = ['id', 'name', 'description', 'created_at'];
            if (!in_array($orderBy, $allowed)) {
                $orderBy = 'name';
            }
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $classes = $query->paginate($perPage);
            
            // Transform the data
            $transformedData = $classes->items();
            foreach ($transformedData as $class) {
                $section = Section::find($class->section_id);
                $class->section_name = $section ? $section->name : null;
                $class->section_id = $section ? $section->name : null;
                
                // Add subjects count
                $class->subjects_count = $class->subjects->count();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Class list fetched successfully',
                'result' => [
                    'data' => $transformedData,
                    'current_page' => $classes->currentPage(),
                    'last_page' => $classes->lastPage(),
                    'per_page' => $classes->perPage(),
                    'total' => $classes->total(),
                    'from' => $classes->firstItem(),
                    'to' => $classes->lastItem()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class list',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created class in storage.
     */
    public function store(StoreClassRequest $request)
    {
        try {
            $class = Classes::create($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Class created successfully',
                'result' => $class
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified class.
     */
    public function show($id)
    {
        try {
            $class = Classes::with('subjects')->find($id);
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }
            // Transform subjects to only include id and name, and force plain array
            $subjects = $class->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ];
            })->values();
            $result = $class->toArray();
            $result['subjects'] = $subjects;
            return response()->json([
                'success' => true,
                'result' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified class in storage.
     */
    public function update(UpdateClassRequest $request, $id)
    {
        try {
            $class = Classes::find($id);
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }
            $class->update($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Class updated successfully',
                'result' => $class
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified class from storage.
     */
    public function destroy($id)
    {
        try {
            $class = Classes::find($id);
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }
            $class->delete();
            return response()->json([
                'success' => true,
                'message' => 'Class deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function assignSubjects(Request $request, $id)
    {
        $request->validate([
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id'
        ]);
        $class = Classes::findOrFail($id);
        $class->subjects()->sync($request->subject_ids);
        $class->merchant_id = $request->merchant_id;
        $class->save();
        return response()->json(['status' => true, 'message' => 'Subjects assigned']);
    }

    public function getSubjects($id)
    {
        try {
            $class = Classes::with('subjects')->find($id);
            
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            // Transform subjects to only include id and name
            $subjects = $class->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Subjects fetched successfully',
                'result' => $subjects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subjects',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function withStudentsAndSection()
    {
        try {
            $classes = Classes::with(['students'])
                ->whereNotNull('section_id') // Only classes with section assigned
                ->whereHas('students')       // Only classes with at least one student
                ->get();

            // Optionally, attach section name for each class
            $classes->map(function($class){
                $class->section_name = Section::find($class->section_id)?->name;
                return $class;
            });

            return response()->json([
                'success' => true,
                'result' => $classes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch filtered classes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 
