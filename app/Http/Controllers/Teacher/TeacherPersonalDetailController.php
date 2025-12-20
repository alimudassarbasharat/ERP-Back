<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\TeacherPersonalDetail;
use App\Http\Requests\Teacher\StoreTeacherPersonalDetailRequest;
use App\Http\Requests\Teacher\UpdateTeacherPersonalDetailRequest;

class TeacherPersonalDetailController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 10);
            $query = TeacherPersonalDetail::with('teacher');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('cnic', 'LIKE', "%$search%")
                      ->orWhere('religion', 'LIKE', "%$search%")
                      ->orWhere('blood_group', 'LIKE', "%$search%")
                      ->orWhere('gender', 'LIKE', "%$search%")
                      ->orWhereHas('teacher', function($q) use ($search) {
                          $q->where('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%")
                            ->orWhere('employee_code', 'LIKE', "%$search%")
                            ->orWhere('email', 'LIKE', "%$search%")
                            ->orWhere('username', 'LIKE', "%$search%")
                            ;
                      });
                });
            }

            $query->orderBy('id', 'desc');
            $details = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Teacher personal details fetched successfully.',
                'result' => $details,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher personal details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $detail = TeacherPersonalDetail::with('teacher')->find($id);
            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher personal detail not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher personal detail',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(StoreTeacherPersonalDetailRequest $request)
    {
        try {
            $detail = TeacherPersonalDetail::create($request->validated());
            $detail->load('teacher');
            return response()->json([
                'success' => true,
                'message' => 'Teacher personal detail created successfully',
                'data' => $detail
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher personal detail',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(UpdateTeacherPersonalDetailRequest $request, $id)
    {
        try {
            $detail = TeacherPersonalDetail::find($id);
            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher personal detail not found'
                ], 404);
            }
            $detail->update($request->validated());
            $detail->load('teacher');
            return response()->json([
                'success' => true,
                'message' => 'Teacher personal detail updated successfully',
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher personal detail',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $detail = TeacherPersonalDetail::find($id);
            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher personal detail not found'
                ], 404);
            }
            $detail->delete();
            return response()->json([
                'success' => true,
                'message' => 'Teacher personal detail deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher personal detail',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
