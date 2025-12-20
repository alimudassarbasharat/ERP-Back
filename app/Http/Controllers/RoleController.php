<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Requests\Role\DeleteRoleRequest;
use App\Http\Requests\Role\AssignPermissionRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = request()->input('per_page', 10);
            $query = Role::with('permissions');
            
            // Only filter by merchant_id if it's provided
            if ($request->has('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }
            
            $roles = $query->orderBy('id', 'desc')
                          ->paginate($perPage);
                          
            $roles->map(function($role){
                $role->permission_count = $role->permissions->count();;
                return $role;
            });

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Throwable $e) {
            Log::error('Role index error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch roles: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $role = Role::create([
                'name' => $request->name,  
                'merchant_id' => $request->merchant_id,
                'description' => $request->description, 
                'created_by' => $request->created_by
            ]);

            // If permissions are provided, sync them
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            return response()->json([
                'status' => true,
                'message' => 'Role created and permissions assigned.',
                'result' => $role->load('permissions'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Role store error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to create role.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Role details fetched.',
                'result' => $role,
            ]);
        } catch (\Throwable $e) {
            Log::error('Role show error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Role not found.'], 404);
        }
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent updating super-admin role
            if ($role->name === 'super-admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update super-admin role.'
                ], 403);
            }

            // Update role name and description
            $role->name = $request->name;
            if ($request->has('description')) {
                $role->description = $request->description;
            }
            $role->save();

            // If permissions are provided, sync them
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role->load('permissions')
            ]);
        } catch (\Throwable $e) {
            Log::error('Role update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(DeleteRoleRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json([
                'status' => true,
                'message' => 'Role deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Role delete error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to delete role.'], 500);
        }
    }

    public function assignRoleToAdmin(Request $request)
    {
        try {
            $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'required|exists:roles,id',
            ]);

            $admin = Admin::findOrFail($request->id);
            $roles = Role::whereIn('id', $request->roles)->get();
            
            if ($roles->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No valid roles found.'
                ], 404);
            }

            $admin->syncRoles($request->roles);

            return response()->json([
                'status' => true,
                'message' => 'Roles assigned to admin successfully.',
                'result' => [
                    'admin_id' => $admin->id,
                    'roles' => $request->roles,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Role assign error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to assign roles.'], 500);
        }
    }

    public function allPermissions()
    {
        try {
            $permissions = Permission::all();
            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching all permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions'
            ], 500);
        }
    }

    public function getRolePermissions($id)
    {
        try {
            $role = Role::findOrFail($id);
            $permissions = $role->permissions;
            
            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching role permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role permissions'
            ], 500);
        }
    }

    public function assignPermissions(AssignPermissionRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($id);
            
            // Sync permissions (this will remove all existing permissions and add the new ones)
            $role->syncPermissions($request->permissions);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully',
                'data' => $role->permissions
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning permissions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions'
            ], 500);
        }
    }
}
