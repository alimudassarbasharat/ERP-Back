<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    /**
     * Get merchant ID from authenticated user
     */
    private function getMerchantId()
    {
        return auth()->user()->merchant_id ?? 1;
    }

    /**
     * Display a listing of workspaces
     */
    public function index()
    {
        try {
            $merchantId = $this->getMerchantId();

            $workspaces = Workspace::forMerchant($merchantId)
                ->active()
                ->withCount(['tickets'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $workspaces
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workspaces',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created workspace
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'nullable|string',
                'color' => 'nullable|string',
                'description' => 'nullable|string'
            ]);

            $merchantId = $this->getMerchantId();

            $workspace = Workspace::create([
                'merchant_id' => $merchantId,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'icon' => $validated['icon'] ?? 'Code',
                'color' => $validated['color'] ?? 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
                'description' => $validated['description'] ?? null,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workspace created successfully',
                'data' => $workspace
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workspace
     */
    public function show($id)
    {
        try {
            $merchantId = $this->getMerchantId();

            $workspace = Workspace::forMerchant($merchantId)
                ->with(['tickets' => function ($query) {
                    $query->with(['assignee', 'reporter', 'subtasks'])
                        ->withCount(['comments', 'attachments']);
                }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $workspace
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified workspace
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'icon' => 'sometimes|string',
                'color' => 'sometimes|string',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean'
            ]);

            $merchantId = $this->getMerchantId();

            $workspace = Workspace::forMerchant($merchantId)->findOrFail($id);

            if (isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $workspace->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'data' => $workspace
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified workspace
     */
    public function destroy($id)
    {
        try {
            $merchantId = $this->getMerchantId();

            $workspace = Workspace::forMerchant($merchantId)->findOrFail($id);
            
            // Check if workspace has tickets
            if ($workspace->tickets()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete workspace with existing tickets'
                ], 400);
            }

            $workspace->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workspace deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workspace statistics
     */
    public function statistics($id)
    {
        try {
            $merchantId = $this->getMerchantId();

            $workspace = Workspace::forMerchant($merchantId)->findOrFail($id);

            $stats = [
                'total_tickets' => $workspace->tickets()->count(),
                'backlog' => $workspace->tickets()->byStatus('backlog')->count(),
                'todo' => $workspace->tickets()->byStatus('todo')->count(),
                'in_progress' => $workspace->tickets()->byStatus('in-progress')->count(),
                'review' => $workspace->tickets()->byStatus('review')->count(),
                'complete' => $workspace->tickets()->byStatus('complete')->count(),
                'by_priority' => [
                    'urgent' => $workspace->tickets()->byPriority('urgent')->count(),
                    'high' => $workspace->tickets()->byPriority('high')->count(),
                    'medium' => $workspace->tickets()->byPriority('medium')->count(),
                    'low' => $workspace->tickets()->byPriority('low')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
