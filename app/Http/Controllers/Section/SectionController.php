<?php

namespace App\Http\Controllers\Section;

use App\Http\Controllers\Controller;
use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Requests\Section\UpdateSectionRequest;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    /**
     * Display a listing of the sections.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Section::with('classes');
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Filter by class_id (sections that belong to a specific class)
            if ($request->has('class_id') && !empty($request->class_id)) {
                $query->whereHas('classes', function($q) use ($request) {
                    $q->where('classes.id', $request->class_id);
                });
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
            $sections = $query->paginate($perPage);
            
            // Transform data to include classes
            $transformedData = $sections->items();
            foreach ($transformedData as $section) {
                $section->classes_list = $section->classes->pluck('name')->toArray();
                $section->classes_count = $section->classes->count();
            }

            return response()->json([
                'success' => true,
                'result' => [
                    'data' => $transformedData,
                    'current_page' => $sections->currentPage(),
                    'last_page' => $sections->lastPage(),
                    'per_page' => $sections->perPage(),
                    'total' => $sections->total(),
                    'from' => $sections->firstItem(),
                    'to' => $sections->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created section in storage.
     *
     * @param  \App\Http\Requests\Section\StoreSectionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSectionRequest $request)
    {
        try {
            $section = Section::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
                'created_by' => Auth::id(),
                'merchant_id' => Auth::user()->merchant_id
            ]);
            
            // Attach classes if provided (many-to-many)
            if ($request->has('class_ids') && is_array($request->class_ids)) {
                $section->classes()->attach($request->class_ids);
            }
            
            // Load classes relationship
            $section->load('classes');

            return response()->json([
                'success' => true,
                'message' => 'Section created successfully',
                'result' => $section
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified section.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $section = Section::with(['creator', 'updater', 'classes'])
                ->findOrFail($id);
            
            // Add classes info for easier frontend consumption
            $section->classes_list = $section->classes->pluck('name')->toArray();
            $section->classes_count = $section->classes->count();

            return response()->json([
                'success' => true,
                'result' => $section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified section in storage.
     *
     * @param  \App\Http\Requests\Section\UpdateSectionRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSectionRequest $request, $id)
    {
        try {
            $section = Section::findOrFail($id);
            $section->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? $section->status,
                'merchant_id' => Auth::user()->merchant_id
            ]);
            
            // Sync classes if provided (many-to-many)
            if ($request->has('class_ids')) {
                if (is_array($request->class_ids) && count($request->class_ids) > 0) {
                    $section->classes()->sync($request->class_ids);
                } else {
                    // If empty array, remove all class associations
                    $section->classes()->detach();
                }
            }
            
            // Load classes relationship
            $section->load('classes');

            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully',
                'result' => $section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified section from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $section = Section::findOrFail($request->id);

            // Check if section has any associated students (if students table has section_id)
            // This check can be added if needed based on your schema

            $section->delete();

            return response()->json([
                'success' => true,
                'message' => 'Section deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sections for dropdown/select options.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSectionsForSelect(Request $request)
    {
        try {
            $query = Section::select('id', 'name')
                ->with('classes:id,name')
                ->orderBy('name');
            
            // Filter by class_id if provided (sections that belong to a specific class)
            if ($request->has('class_id') && !empty($request->class_id)) {
                $query->whereHas('classes', function($q) use ($request) {
                    $q->where('classes.id', $request->class_id);
                });
            }
            
            $sections = $query->get();
            
            // Transform to include classes
            $transformed = $sections->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'classes' => $section->classes->map(function($class) {
                        return [
                            'id' => $class->id,
                            'name' => $class->name
                        ];
                    })->toArray(),
                    'classes_count' => $section->classes->count()
                ];
            });

            return response()->json([
                'success' => true,
                'result' => $transformed
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign classes to a section (bulk).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignClasses(Request $request, $id)
    {
        try {
            $request->validate([
                'class_ids' => 'required|array',
                'class_ids.*' => 'exists:classes,id'
            ]);
            
            $section = Section::findOrFail($id);
            $section->classes()->sync($request->class_ids);
            
            // Load relationships for response
            $section->load('classes');
            $section->classes_list = $section->classes->pluck('name')->toArray();
            $section->classes_count = $section->classes->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Classes assigned successfully',
                'result' => $section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create sections.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'sections' => 'required|array|min:1',
                'sections.*.name' => 'required|string|max:255',
                'sections.*.description' => 'required|string',
                'sections.*.status' => 'sometimes|in:active,inactive',
                'sections.*.class_ids' => 'sometimes|array',
                'sections.*.class_ids.*' => 'exists:classes,id',
            ]);

            $merchantId = Auth::user()->merchant_id;
            $createdBy = Auth::id();
            $created = [];
            $failed = [];

            foreach ($request->sections as $index => $sectionData) {
                try {
                    // Check for duplicate name within merchant
                    $exists = Section::where('name', $sectionData['name'])
                        ->where('merchant_id', $merchantId)
                        ->exists();
                    
                    if ($exists) {
                        $failed[] = [
                            'index' => $index,
                            'name' => $sectionData['name'],
                            'error' => 'Section name already exists'
                        ];
                        continue;
                    }

                    $section = Section::create([
                        'name' => $sectionData['name'],
                        'description' => $sectionData['description'],
                        'status' => $sectionData['status'] ?? 'active',
                        'created_by' => $createdBy,
                        'merchant_id' => $merchantId
                    ]);

                    // Attach classes if provided
                    if (isset($sectionData['class_ids']) && is_array($sectionData['class_ids'])) {
                        $section->classes()->attach($sectionData['class_ids']);
                    }

                    $section->load('classes');
                    $created[] = $section;
                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'name' => $sectionData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => sprintf('Created %d section(s), %d failed', count($created), count($failed)),
                'result' => [
                    'created' => $created,
                    'failed' => $failed,
                    'total' => count($request->sections),
                    'success_count' => count($created),
                    'failed_count' => count($failed)
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk create sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 