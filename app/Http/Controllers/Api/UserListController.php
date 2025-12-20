<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserListController extends Controller
{
    /**
     * Get all assignable users (admins, teachers, students)
     */
    public function getAssignableUsers(Request $request)
    {
        try {
            $merchantId = auth()->user()->merchant_id ?? 'SUPER123';
            $users = [];

            // Get Admins
            $admins = DB::table('admins')
                ->where('merchant_id', $merchantId)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'email', DB::raw("'admin' as type"))
                ->get();

            foreach ($admins as $admin) {
                $users[] = [
                    'id' => 'admin_' . $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'type' => 'Admin',
                    'user_id' => $admin->id,
                    'user_type' => 'admin'
                ];
            }

            // Get Teachers
            $teachers = DB::table('teachers')
                ->where('merchant_id', $merchantId)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'email', DB::raw("'teacher' as type"))
                ->get();

            foreach ($teachers as $teacher) {
                $users[] = [
                    'id' => 'teacher_' . $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'type' => 'Teacher',
                    'user_id' => $teacher->id,
                    'user_type' => 'teacher'
                ];
            }

            // Get Students
            $students = DB::table('students')
                ->where('merchant_id', $merchantId)
                ->select(
                    'id',
                    DB::raw("CONCAT(first_name, ' ', last_name) as name"),
                    DB::raw("'' as email"),
                    'roll_number',
                    DB::raw("'student' as type")
                )
                ->limit(100) // Limit for performance
                ->get();

            foreach ($students as $student) {
                $users[] = [
                    'id' => 'student_' . $student->id,
                    'name' => $student->name,
                    'roll_number' => $student->roll_number,
                    'type' => 'Student',
                    'user_id' => $student->id,
                    'user_type' => 'student'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
