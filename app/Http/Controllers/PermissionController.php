<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Requests\Permission\DeletePermissionRequest;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $permissions = Permission::paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions'
            ], 500);
        }
    }

    public function store(StorePermissionRequest $request)
    {
        try {
            DB::beginTransaction();

            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $permission = Permission::findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Permission fetched successfully.',
                'result' => $permission,
            ]);
        } catch (\Throwable $e) {
            Log::error('Permission show error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Permission not found.'], 404);
        }
    }

    public function update(UpdatePermissionRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $permission = Permission::findOrFail($id);
            $permission->update([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission'
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $permission = Permission::findOrFail($id);
            
            // Check if permission is being used by any roles
            $rolesWithPermission = DB::table('role_has_permissions')
                ->where('permission_id', $id)
                ->count();

            if ($rolesWithPermission > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission as it is assigned to one or more roles'
                ], 422);
            }

            $permission->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission'
            ], 500);
        }
    }
}
