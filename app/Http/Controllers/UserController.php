<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\TeacherContactDetail;
use App\Models\ContactInformation;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'result' => User::all()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'result' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'result' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'result' => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Search students and employees by name, phone, roll number, or employee id.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        if (!$query) {
            return response()->json(['results' => []]);
        }

        // Search students (with contact info)
        $students = Student::with('contactInfo')->where(function($q) use ($query) {
            $q->where('first_name', 'like', "%$query%")
              ->orWhere('last_name', 'like', "%$query%")
              ->orWhere('roll_number', 'like', "%$query%")
              ->orWhere('cnic_number', 'like', "%$query%")
              ->orWhere('id', 'like', "%$query%")
              ;
        })->limit(10)->get()->map(function($student) {
            return [
                'id' => $student->id,
                'type' => 'Student',
                'firstName' => snakeToTitle($student->first_name),
                'lastName' => snakeToTitle($student->last_name),
                'middleName' => null,
                'phone' => $student->contactInfo ? $student->contactInfo->reporting_number : null,
                'rollNumber' => $student->roll_number,
                'employeeId' => null,
                'avatar' => $student->photo_path ? asset('student-profiles/' . $student->photo_path) : null,
            ];
        });

        // Search teachers (with contact details)
        $teachers = Teacher::with('contactDetails', 'personalDetails')->where(function($q) use ($query) {
            $q->where('first_name', 'like', "%$query%")
              ->orWhere('last_name', 'like', "%$query%")
              ->orWhere('employee_code', 'like', "%$query%")
              ->orWhere('email', 'like', "%$query%")
              ->orWhere('designation', 'like', "%$query%")
              ->orWhere('id', 'like', "%$query%")
              ;
        })->limit(10)->get()->map(function($teacher) {
            // Use the /storage/teacher-profiles/ path for avatar
            $profilePic = null;
            if ($teacher->profile_picture) {
                $profilePic = 'storage/teacher-profiles/' . basename($teacher->profile_picture);
            } elseif (optional($teacher->personalDetails)->profile_picture) {
                $profilePic = 'storage/teacher-profiles/' . basename($teacher->personalDetails->profile_picture);
            }
            return [
                'id' => $teacher->id,
                'type' => 'Employee',
                'firstName' => snakeToTitle($teacher->first_name),
                'lastName' => snakeToTitle($teacher->last_name),
                'middleName' => null,
                'phone' => $teacher->contactDetails ? $teacher->contactDetails->phone_number : null,
                'rollNumber' => null,
                'employeeId' => $teacher->employee_code,
                'avatar' => $profilePic ? asset($profilePic) : null,
            ];
        });

        $results = $students->merge($teachers)->values();
        return response()->json([
            'status' => true,
            'message' => 'Search results fetched successfully.',
            'result' => $results
        ]);
    }
} 