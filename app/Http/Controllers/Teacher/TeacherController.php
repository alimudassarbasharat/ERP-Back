<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Models\Teacher;
use App\Models\TeacherPersonalDetail;
use App\Models\TeacherContactDetail;
use App\Models\TeacherProfessionalDetail;
use App\Models\TeacherAdditionalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 10);

            $query = Teacher::with('personalDetails', 'contactDetails', 'professionalDetails', 'additionalDetails', 'department')
                ->select('teachers.*');

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%$search%")
                        ->orWhere('last_name', 'ILIKE', "%$search%")
                        ->orWhere('email', 'ILIKE', "%$search%");
                });
            }

            // Filters
            if ($email = $request->input('email')) {
                $query->where('email', 'ILIKE', "%$email%");
            }

            // if ($role = $request->input('role')) {
            //     $query->whereHas('roles', fn($q) => $q->where('name', $role));
            // }

            if (!is_null($request->input('status'))) {
                $query->where('status', $request->input('status'));
            }


            // Sorting
            if ($orderBy = $request->input('orderBy')) {
                $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
                $column = ltrim($orderBy, '-');
                if (Schema::hasColumn('teachers', $column)) {
                    $query->orderBy($column, $direction);
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            $teachers = $query->paginate($perPage);

            // Format profile picture URLs
            foreach ($teachers as $teacher) {
                if ($teacher->personalDetails && $teacher->personalDetails->profile_picture) {
                    $teacher->personalDetails->profile_picture = 'teacher-profiles/' . basename($teacher->personalDetails->profile_picture);
                    $teacher->designation = snakeToTitle($teacher->designation);
                    $teacher->department  = snakeToTitle($teacher->department);
                    $teacher->status      = snakeToTitle($teacher->status);
                    $teacher->first_name  = snakeToTitle($teacher->first_name);
                    $teacher->last_name   = snakeToTitle($teacher->last_name);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Teachers fetched successfully.',
                'result' => $teachers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teachers',
                'error' => config('app.debug') ? $e->getMessage() . ' ' . $e->getLine() : null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $teacher = Teacher::with(['personalDetails', 'contactDetails', 'professionalDetails', 'additionalDetails'])->find($id);

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            // Format dates and text
            $teacher->joining_date              = formatDatePk($teacher->joining_date);
            $teacher->date_of_birth             = formatDatePk($teacher->date_of_birth, true);
            $teacher->cnic_expiry_date          = formatDatePk($teacher->cnic_expiry_date);
            $teacher->first_name                = capitalizeWords($teacher->first_name);
            $teacher->last_name                 = capitalizeWords($teacher->last_name);
            $teacher->department                = snakeToTitle($teacher->department);
            $teacher->designation               = snakeToTitle($teacher->designation);
            $teacher->qualification             = snakeToTitle($teacher->qualification);
            $teacher->specialization            = snakeToTitle($teacher->specialization);
            $teacher->status                    = snakeToTitle($teacher->status);

            // Format personal details
            if ($teacher->personalDetails) {
                $teacher->personalDetails->gender   = snakeToTitle($teacher->personalDetails->gender);
                $teacher->personalDetails->religion = snakeToTitle($teacher->personalDetails->religion);
                $teacher->personalDetails->qualification = snakeToTitle($teacher->personalDetails->qualification);

                // Format profile picture URL
                if ($teacher->personalDetails->profile_picture) {
                    $teacher->personalDetails->profile_picture = 'teacher-profiles/' . basename($teacher->personalDetails->profile_picture);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Teacher fetched successfully.',
                'result' => $teacher
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {

        // dd($request->all());
        DB::beginTransaction();
        try {
            $validated = app(\App\Http\Requests\StoreTeacherRequest::class)->validated();

            // 1. Create the main teacher record
            $teacher = Teacher::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'employee_code' => $validated['employee_code'],
                'email' => $validated['email'],
                'username' => $validated['username'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'designation' => $validated['designation'],
                'department_id' => $validated['department'],
                'qualification' => $validated['qualification'],
                // 'specialization' => $validated['specialization'],
                'years_of_experience' => $validated['years_of_experience'],
                'joining_date' => $validated['joining_date'],
                'salary' => $validated['salary'],
                'bank_account_details' => $validated['bank_account_details'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 2. Handle profile picture upload
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('teacher-profiles', 'public');
            }

            // 3. Create sub-table records
            TeacherPersonalDetail::create([
                'teacher_id' => $teacher->id,
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'cnic' => $validated['cnic'],
                'religion' => $validated['religion'],
                'blood_group' => $validated['blood_group'] ?? null,
                'profile_picture' => $profilePicturePath,
                // 'bank_account_details' => $validated['bank_account_details'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            TeacherContactDetail::create([
                'teacher_id' => $teacher->id,
                'address' => $validated['address'],
                'phone_number' => $validated['phone_number'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'emergency_contact' => $validated['emergency_contact'] ?? "null",
            ]);

            TeacherProfessionalDetail::create([
                'teacher_id' => $teacher->id,
                'qualification' => $validated['qualification'],
                'designation' => $validated['designation'],
                'years_of_experience' => $validated['years_of_experience'],
                'joining_date' => $validated['joining_date'],
                'department_id' => $validated['department'],
                'specialization' => $validated['specialization'] ?? "null",
            ]);

            TeacherAdditionalDetail::create([
                'teacher_id' => $teacher->id,
            ]);

            DB::commit();

            $teacher->load('personalDetails', 'contactDetails', 'professionalDetails', 'additionalDetails');
            return response()->json([
                'status' => 'success',
                'message' => 'Teacher created successfully',
                'data' => $teacher
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Teacher not found'
                ], 404);
            }

            // Validate same as store
            $validated = app(\App\Http\Requests\StoreTeacherRequest::class)->validated();

            // 1. Update Teacher
            $teacher->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'employee_code' => $validated['employee_code'],
                'email' => $validated['email'],
                'username' => $validated['username'] ?? $teacher->username,
                // Password update only if provided
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : $teacher->password,
                'status' => $validated['status'],
                'designation' => $validated['designation'],
                'department_id' => $validated['department'],
                'qualification' => $validated['qualification'],
                'years_of_experience' => $validated['years_of_experience'],
                'joining_date' => $validated['joining_date'],
                'salary' => $validated['salary'],
                'bank_account_details' => $validated['bank_account_details'] ?? $teacher->bank_account_details,
                'remarks' => $validated['remarks'] ?? $teacher->remarks,
            ]);

            // 2. Handle profile picture update
            $profilePicturePath = $teacher->personalDetails->profile_picture ?? null;
            if ($request->hasFile('profile_picture')) {
                if ($profilePicturePath && \Storage::disk('public')->exists($profilePicturePath)) {
                    \Storage::disk('public')->delete($profilePicturePath);
                }
                $profilePicturePath = $request->file('profile_picture')->store('teacher-profiles', 'public');
            }

            // 3. Update TeacherPersonalDetail
            $teacher->personalDetails()->updateOrCreate(
                ['teacher_id' => $teacher->id],
                [
                    'gender' => $validated['gender'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'cnic' => $validated['cnic'],
                    'religion' => $validated['religion'],
                    'blood_group' => $validated['blood_group'] ?? null,
                    'profile_picture' => $profilePicturePath,
                    'remarks' => $validated['remarks'] ?? null,
                ]
            );

            // 4. Update TeacherContactDetail
            $teacher->contactDetails()->updateOrCreate(
                ['teacher_id' => $teacher->id],
                [
                    'address' => $validated['address'],
                    'phone_number' => $validated['phone_number'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                ]
            );

            // 5. Update TeacherProfessionalDetail
            $teacher->professionalDetails()->updateOrCreate(
                ['teacher_id' => $teacher->id],
                [
                    'qualification' => $validated['qualification'],
                    'designation' => $validated['designation'],
                    'years_of_experience' => $validated['years_of_experience'],
                    'joining_date' => $validated['joining_date'],
                    'department_id' => $validated['department'],
                    'specialization' => $validated['specialization'] ?? null,
                ]
            );

            // 6. Update TeacherAdditionalDetail (empty for now)
            $teacher->additionalDetails()->updateOrCreate(
                ['teacher_id' => $teacher->id],
                []
            );

            DB::commit();

            $teacher->load('personalDetails', 'contactDetails', 'professionalDetails', 'additionalDetails');

            return response()->json([
                'status' => 'success',
                'message' => 'Teacher updated successfully',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {

            $teacher = Teacher::find($id);

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            // Delete profile picture if exists
            if ($teacher->personalDetails && $teacher->personalDetails->profile_picture) {
                Storage::disk('public')->delete($teacher->personalDetails->profile_picture);
            }

            // Delete related records
            $teacher->personalDetails()->delete();
            $teacher->contactDetails()->delete();
            $teacher->delete();


            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getTeacherRoles($id)
    {
        try {
            $teacher = Teacher::findOrFail($id);
            $roles = $teacher->roles()->get();

            return response()->json([
                'status' => 'success',
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch teacher roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function storeMultiple(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'teachers' => 'required|array|min:1',
                'teachers.*.first_name' => 'required|string|max:255',
                'teachers.*.last_name' => 'required|string|max:255',
                'teachers.*.email' => 'required|email',
                'teachers.*.phone_number' => 'required|string|max:20',
                'teachers.*.employee_code' => 'required|string',
                'teachers.*.designation' => 'required|string',
                'teachers.*.status' => 'required|in:active,inactive,on_leave'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate employee codes in the request
            $employeeCodes = collect($request->teachers)->pluck('employee_code');
            $duplicateCodes = $employeeCodes->duplicates();
            if ($duplicateCodes->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate employee codes found in the request'
                ], 422);
            }

            // Check for existing employee codes in database
            $existingCodes = Teacher::whereIn('employee_code', $employeeCodes)->pluck('employee_code');
            if ($existingCodes->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some employee codes already exist in the system'
                ], 422);
            }

            // Check for duplicate emails in the request
            $emails = collect($request->teachers)->pluck('email');
            $duplicateEmails = $emails->duplicates();
            if ($duplicateEmails->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate email addresses found in the request'
                ], 422);
            }

            // Check for existing emails in database
            $existingEmails = Teacher::whereIn('email', $emails)->pluck('email');
            if ($existingEmails->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some email addresses already exist in the system'
                ], 422);
            }

            DB::beginTransaction();

            $createdTeachers = [];

            foreach ($request->teachers as $teacherData) {
                // Generate username from email
                $username = explode('@', $teacherData['email'])[0];

                // Generate a random password
                $password = Str::random(8);

                // Create teacher
                $teacher = Teacher::create([
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'email' => $teacherData['email'],
                    'phone' => $teacherData['phone_number'],
                    'employee_code' => $teacherData['employee_code'],
                    'department_id' => $teacherData['department'],
                    'designation' => $teacherData['designation'],
                    'status' => $teacherData['status'],
                    'username' => $username,
                    'password' => Hash::make($password),
                    'created_by' => auth()->id()
                ]);

                TeacherPersonalDetail::create([
                    'teacher_id' => $teacher->id,
                    'gender' => $teacherData['gender'],
                    'cnic' => $teacherData['cnic'],
                ]);

                TeacherContactDetail::create([
                    'teacher_id' => $teacher->id,
                    'address' => $teacherData['address'],
                    'phone_number' => $teacherData['phone_number'],

                ]);

                TeacherProfessionalDetail::create([
                    'teacher_id' => $teacher->id,
                    // 'qualification' => $teacherData['qualification'],
                    'designation' => $teacherData['designation'],
                    // 'years_of_experience' => $teacherData['years_of_experience'],
                    'department_id' => $teacherData['department'],
                ]);

                $createdTeachers[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->full_name,
                    'email' => $teacher->email,
                    'username' => $teacher->username,
                    'password' => $password // Include password in response for admin to share with teachers
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdTeachers) . ' teachers created successfully',
                'data' => $createdTeachers
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teachers',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available form styles for preview in the Forms tab.
     */
    public function getFormStyles()
    {
        $baseUrl = config('app.url') . '/form-previews';
        $styles = [
            [
                'id' => 1,
                'name' => 'Style 1',
                'description' => 'Modern design with gradient header',
                'preview' => $baseUrl . '/style1.png',
            ],
            [
                'id' => 2,
                'name' => 'Style 2',
                'description' => 'Classic professional layout',
                'preview' => $baseUrl . '/style2.png',
            ],
            [
                'id' => 3,
                'name' => 'Style 3',
                'description' => 'Minimalist design with accent colors',
                'preview' => $baseUrl . '/style3.png',
            ],
            [
                'id' => 4,
                'name' => 'Style 4',
                'description' => 'Corporate style with bold typography',
                'preview' => $baseUrl . '/style4.png',
            ],
            [
                'id' => 5,
                'name' => 'Style 5',
                'description' => 'Creative layout with modern elements',
                'preview' => $baseUrl . '/style5.png',
            ],
            [
                'id' => 6,
                'name' => 'Style 6',
                'description' => 'Elegant design with subtle patterns',
                'preview' => $baseUrl . '/style6.png',
            ],
        ];
        return response()->json([
            'success' => true,
            'result' => $styles
        ]);
    }

    /**
     * Render a Blade template as an image for preview.
     */
    public function previewFormStyle($styleId)
    {
        $view = "employee-id-cards.style{$styleId}";
        if (!view()->exists($view)) {
            abort(404, 'Template not found');
        }

        // Sample data for preview
        $sampleData = [
            'profile_image' => 'https://randomuser.me/api/portraits/men/32.jpg',
            'employee_name' => 'John Doe',
            'designation' => 'Software Engineer',
            'employee_id' => 'EMP-123456',
            'dob' => '01/01/1990',
            'phone' => '+123 456 7890',
            'company_logo' => 'https://via.placeholder.com/48x48?text=Logo',
            'company_name' => 'ACME Corp',
            'company_tagline' => 'Innovation for Tomorrow',
        ];

        $html = view($view, $sampleData)->render();

        $image = Browsershot::html($html)
            ->windowSize(600, 400)
            ->setOption('args', ['--no-sandbox'])
            ->setScreenshotType('png')
            ->screenshot();

        return response($image)->header('Content-Type', 'image/png');
    }
}
