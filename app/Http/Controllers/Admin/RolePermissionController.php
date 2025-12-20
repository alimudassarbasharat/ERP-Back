<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    /**
     * Get all roles with their permissions
     */
    public function getRoles()
    {
        try {
            $roles = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific role with permissions
     */
    public function getRole($roleId)
    {
        try {
            $role = Role::with(['permissions' => function($query) {
                $query->select('name', 'id');
            }])->find($roleId);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? ucwords(str_replace('-', ' ', $role->name)),
                    'description' => $role->description ?? null,
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissions()
    {
        try {
            $permissions = Permission::all();
            $groupedPermissions = [];

            foreach ($permissions as $permission) {
                $module = $permission->module ?? 'general';
                
                if (!isset($groupedPermissions[$module])) {
                    $groupedPermissions[$module] = [
                        'module' => $module,
                        'display_name' => ucwords(str_replace('-', ' ', $module)),
                        'description' => $permission->description ?? '',
                        'permissions' => []
                    ];
                }
                
                $groupedPermissions[$module]['permissions'][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'action' => $permission->action ?? '',
                    'module' => $permission->module ?? '',
                    'display_name' => ucwords(str_replace('-', ' ', $permission->action ?? '')),
                    'description' => $permission->description ?? ''
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_values($groupedPermissions)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new role
     */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'api'
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->givePermissionTo($permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role->load('permissions')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a role and its permissions
     */
    public function updateRole(Request $request, $roleId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:roles,name,' . $roleId,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($roleId);
            
            if ($request->has('name')) {
                $role->update(['name' => $request->name]);
            }

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            
            // Check if role is assigned to any admins
            $adminsCount = Admin::role($role->name)->count();
            if ($adminsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete role. It is assigned to {$adminsCount} admin(s)."
                ], 400);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign role to admin
     */
    public function assignRoleToAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|exists:admins,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = Admin::findOrFail($request->admin_id);
            $role = Role::findOrFail($request->role_id);

            $admin->syncRoles([$role->name]);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'admin' => $admin->load('roles'),
                    'role' => $role
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admins with their roles and permissions
     */
    public function getAdminsWithRoles()
    {
        try {
            $admins = Admin::with(['roles', 'roles.permissions'])->get()->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'merchant_id' => $admin->merchant_id,
                    'status' => $admin->status,
                    'roles' => $admin->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                            'permissions_count' => $role->permissions->count()
                        ];
                    }),
                    'all_permissions' => $admin->getAllPermissions()->pluck('name'),
                    'created_at' => $admin->created_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $admins
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admins with roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admin permissions summary
     */
    public function getPermissionsSummary()
    {
        try {
            $totalPermissions = Permission::count();
            $totalRoles = Role::count();
            $totalAdmins = Admin::count();
            
            $roleStats = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'role' => $role->name,
                    'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                    'permissions_count' => $role->permissions->count(),
                    'admins_count' => Admin::role($role->name)->count()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_permissions' => $totalPermissions,
                        'total_roles' => $totalRoles,
                        'total_admins' => $totalAdmins
                    ],
                    'role_stats' => $roleStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (admins and regular users) with their roles
     */
    public function getAllUsersWithRoles()
    {
        try {
            // Get users with their roles and permissions
            $users = \App\Models\User::with(['roles', 'roles.permissions'])->get()->map(function ($user) {
                $admin = \App\Models\Admin::where('user_id', $user->id)->first();
                
                return [
                    'id' => $user->id,
                    'name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'type' => $admin ? 'admin' : 'user',
                    'admin_id' => $admin ? $admin->id : null,
                    'merchant_id' => $admin ? $admin->merchant_id : null,
                    'status' => $admin ? $admin->status : 'active',
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name ?? ucwords(str_replace('-', ' ', $role->name)),
                            'permissions_count' => $role->permissions->count()
                        ];
                    }),
                    'all_permissions' => $user->getAllPermissions()->pluck('name'),
                    'created_at' => $user->created_at->format('Y-m-d H:i:s')
                ];
            });

            // Also get admins without linked users
            $adminsWithoutUsers = \App\Models\Admin::whereNull('user_id')->get()->map(function ($admin) {
                $role = \Spatie\Permission\Models\Role::find($admin->role_id);
                
                return [
                    'id' => 'admin_' . $admin->id, // Prefix to avoid ID conflicts
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'type' => 'admin_legacy',
                    'admin_id' => $admin->id,
                    'merchant_id' => $admin->merchant_id,
                    'status' => $admin->status,
                    'roles' => $role ? [[
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? ucwords(str_replace('-', ' ', $role->name)),
                        'permissions_count' => $role->permissions()->count()
                    ]] : [],
                    'all_permissions' => $role ? $role->permissions()->pluck('name') : [],
                    'created_at' => $admin->created_at->format('Y-m-d H:i:s')
                ];
            });

            $allUsers = $users->concat($adminsWithoutUsers);

            return response()->json([
                'success' => true,
                'data' => $allUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users with roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign role to any user (not just admin)
     */
    public function assignRoleToUser(\App\Http\Requests\Admin\RolePermission\AssignPermissionsRequest $request)
    {
        try {

            $user = \App\Models\User::find($request->user_id);
            $role = \Spatie\Permission\Models\Role::find($request->role_id);

            if (!$user || !$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'User or role not found'
                ], 404);
            }

            // Remove existing roles and assign new role
            $user->syncRoles([$role->name]);

            return response()->json([
                'success' => true,
                'message' => "Role '{$role->display_name}' assigned to user successfully",
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_name' => $role->name,
                    'role_display_name' => $role->display_name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role to user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserPermissions()
    {
        try {
            $authUser = \Illuminate\Support\Facades\Auth::user();
            
            // Handle both Admin and User authentication
            if ($authUser instanceof \App\Models\Admin) {
                // Admin is authenticated, get their linked user
                if ($authUser->user_id) {
                    $user = \App\Models\User::find($authUser->user_id);
                    if (!$user) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Linked user not found for admin'
                        ], 404);
                    }
                } else {
                    // Admin has no linked user, create response based on admin's role
                    $adminRole = \Spatie\Permission\Models\Role::find($authUser->role_id);
                    if ($adminRole) {
                        $permissions = $adminRole->permissions()->pluck('name');
                        return response()->json([
                            'success' => true,
                            'permissions' => $permissions,
                            'roles' => [$adminRole->name],
                            'role' => $adminRole->name,
                            'user_id' => $authUser->id,
                            'user_name' => $authUser->name ?? 'Admin User',
                            'admin_id' => $authUser->id,
                            'merchant_id' => $authUser->merchant_id
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Admin role not found'
                        ], 404);
                    }
                }
            } else {
                // User is directly authenticated
                $user = $authUser;
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get user's roles and permissions
            $roles = $user->getRoleNames();
            $permissions = $user->getAllPermissions()->pluck('name');
            $primaryRole = $roles->first();

            // Get admin info if this user is linked to an admin
            $admin = \App\Models\Admin::where('user_id', $user->id)->first();

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
                'roles' => $roles,
                'role' => $primaryRole,
                'user_id' => $user->id,
                'user_name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
                'admin_id' => $admin ? $admin->id : null,
                'merchant_id' => $admin ? $admin->merchant_id : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
