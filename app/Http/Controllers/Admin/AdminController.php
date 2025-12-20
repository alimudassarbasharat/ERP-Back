<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 10);

            $query = Admin::select('id', 'name', 'email', 'status', 'merchant_id')
                ->with(['roles:id,name']); // Eager-load roles only with needed fields
            
            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%$search%")
                      ->orWhere('email', 'ILIKE', "%$search%");
                });
            }
            
            // Filters
            if ($email = $request->input('email')) {
                $query->where('email', 'ILIKE', "%$email%");
            }
            
            if ($role = $request->input('role')) {
                $query->whereHas('roles', fn($q) => $q->where('name', $role));
            }
            
            if (!is_null($request->input('status'))) {
                $query->where('status', $request->input('status'));
            }
            
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }
            
            // Sorting
            if ($orderBy = $request->input('orderBy')) {
                $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
                $column = ltrim($orderBy, '-');
                if (Schema::hasColumn('admins', $column)) {
                    $query->orderBy($column, $direction);
                }
            } else {
                $query->orderBy('id', 'desc');
            }
            
            $admins = $query->paginate($perPage);
            
            // Transform the response to include role names
            $admins->getCollection()->transform(function ($admin) {
                $admin->role_name = $admin->roles->pluck('name')->join(', ');
                $admin->roles = $admin->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name
                    ];
                });
                return $admin;
            });

            return response()->json([
                'status' => true,
                'message' => 'Admins fetched successfully.',
                'result' => $admins,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admins',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $admin = Admin::with('role')->find($id);
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:admins',
                'password' => 'required|string|min:8',
                'phone_number' => 'nullable|string|max:20',
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role_id' => $request->role_id,
                'status' => $request->status,
                'merchant_id' => $request->merchant_id ?? null,
                'created_by' => $request->created_by ?? null,
            ]);

            $admin->load('role');

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully',
                'data' => $admin
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::find($id);
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:admins,email,' . $id,
                'phone_number' => 'nullable|string|max:20',
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'role_id' => $request->role_id,
                'status' => $request->status
            ]);

            $admin->load('role');

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully',
                'data' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update admin',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $admin = Admin::find($id);
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete admin',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAdminRoles($id)
    {
        try {
            $admin = Admin::findOrFail($id);
            $roles = $admin->roles()->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch admin roles'
            ], 500);
        }
    }
} 