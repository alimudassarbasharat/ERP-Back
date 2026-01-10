<?php

namespace App\Http\Controllers\Classes;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Http\Requests\Class\StoreClassRequest;
use App\Http\Requests\Class\UpdateClassRequest;
use Illuminate\Http\Request;
use App\Models\Section;
use Illuminate\Support\Facades\Schema;

class ClassController extends Controller
{
    /**
     * Display a listing of the classes.
     */
    public function index(Request $request)
    {
        try {
            $query = Classes::with(['subjects', 'sections']);
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Section filter (many-to-many relationship)
            if ($request->has('section_id') && !empty($request->section_id)) {
                $query->whereHas('sections', function($q) use ($request) {
                    $q->where('sections.id', $request->section_id);
                });
            }
            
            // Sorting (fallback to existing column)
            $requestedSort = $request->get('sort_by', 'name');
            $direction = $request->get('sort_direction', 'asc');
            $allowed = ['id', 'name', 'description', 'created_at'];
            $orderBy = in_array($requestedSort, $allowed) ? $requestedSort : 'name';
            if (!Schema::hasColumn('classes', $orderBy)) {
                $orderBy = Schema::hasColumn('classes', 'name') ? 'name' : 'id';
            }
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $classes = $query->paginate($perPage);
            
            // Transform the data
            $transformedData = $classes->items();
            foreach ($transformedData as $class) {
                // Add "Class" prefix to the name
                $class->display_name = 'Class ' . $class->name;
                
                // Get sections (many-to-many)
                $sections = $class->sections;
                if ($sections && $sections->count() > 0) {
                    // Join section names with comma
                    $class->section_name = $sections->pluck('name')->join(', ');
                    $class->sections_list = $sections->pluck('name')->toArray();
                    $class->sections_count = $sections->count();
                } else {
                    $class->section_name = null;
                    $class->sections_list = [];
                    $class->sections_count = 0;
                }
                
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

    /**
     * Assign sections to a class (bulk).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignSections(Request $request, $id)
    {
        try {
            $request->validate([
                'section_ids' => 'required|array',
                'section_ids.*' => 'exists:sections,id'
            ]);
            
            $class = Classes::findOrFail($id);
            $class->sections()->sync($request->section_ids);
            
            // Load relationships for response
            $class->load(['sections', 'subjects']);
            
            return response()->json([
                'success' => true,
                'message' => 'Sections assigned successfully',
                'result' => $class
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign sections',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
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

    /**
     * Bulk create classes.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'classes' => 'required|array|min:1',
                'classes.*.name' => 'required|string|max:255',
                'classes.*.description' => 'nullable|string|max:500',
                'classes.*.section_id' => 'nullable|exists:sections,id',
                'classes.*.subject_ids' => 'sometimes|array',
                'classes.*.subject_ids.*' => 'exists:subjects,id',
            ]);

            $merchantId = auth()->user()->merchant_id;
            $created = [];
            $failed = [];

            foreach ($request->classes as $index => $classData) {
                try {
                    // Check for duplicate name within merchant
                    $exists = Classes::where('name', $classData['name'])
                        ->where('merchant_id', $merchantId)
                        ->exists();
                    
                    if ($exists) {
                        $failed[] = [
                            'index' => $index,
                            'name' => $classData['name'],
                            'error' => 'Class name already exists'
                        ];
                        continue;
                    }

                    $class = Classes::create([
                        'name' => $classData['name'],
                        'description' => $classData['description'] ?? null,
                        'section_id' => $classData['section_id'] ?? null,
                        'merchant_id' => $merchantId
                    ]);

                    // Attach subjects if provided
                    if (isset($classData['subject_ids']) && is_array($classData['subject_ids'])) {
                        $class->subjects()->attach($classData['subject_ids']);
                    }

                    // Attach sections if provided (many-to-many)
                    if (isset($classData['section_ids']) && is_array($classData['section_ids'])) {
                        $class->sections()->attach($classData['section_ids']);
                    }

                    $class->load(['subjects', 'sections']);
                    $created[] = $class;
                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'name' => $classData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => sprintf('Created %d class(es), %d failed', count($created), count($failed)),
                'result' => [
                    'created' => $created,
                    'failed' => $failed,
                    'total' => count($request->classes),
                    'success_count' => count($created),
                    'failed_count' => count($failed)
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk create classes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 
