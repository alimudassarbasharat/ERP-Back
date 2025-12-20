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
            $query = Section::query();
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
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

            return response()->json([
                'success' => true,
                'result' => [
                    'data' => $sections->items(),
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
                'created_by' => Auth::id(),
                'merchant_id' => Auth::user()->merchant_id
            ]);

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
                'merchant_id' => Auth::user()->merchant_id
            ]);

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

            // Check if section has any associated classes
            if ($section->classes()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete section with associated classes'
                ], 422);
            }

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
    public function getSectionsForSelect()
    {
        try {
            $sections = Section::select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'result' => $sections
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 