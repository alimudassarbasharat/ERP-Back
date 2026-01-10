<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;

use App\Http\Requests\Student\StudentListRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     */
    public function index(Request $request): JsonResponse
    {
        $challanMonth = $request->input('challan_month', now()->format('F Y'));
        // Convert 'June 2025' to '2025-06-01' for Postgres date comparison
        try {
            $monthFor = Carbon::createFromFormat('F Y', $challanMonth)->format('Y-m-01');
        } catch (\Exception $e) {
            $monthFor = now()->format('Y-m-01');
        }

        // Check if class_id column exists in students table
        $hasClassId = Schema::hasColumn('students', 'class_id');
        $hasSectionId = Schema::hasColumn('students', 'section_id');

        // Build select array conditionally based on column existence
        $selectArray = [
            'students.*',
            'family_infos.father_name as family_father_name',
        ];

        if ($hasClassId) {
            $selectArray[] = 'classes.name as class_name';
            $selectArray[] = DB::raw('COALESCE(fs.total_amount, fd.monthly_fee, 0) as monthly_fee');
            $selectArray[] = DB::raw('COALESCE(fs.total_amount, fd.monthly_fee, 0) as total_amount');
            $selectArray[] = DB::raw('COALESCE(fs.balance_amount, COALESCE(fs.total_amount, fd.monthly_fee, 0) - COALESCE(fs.paid_amount, 0), 0) as remaining_amount');
        } else {
            $selectArray[] = DB::raw('NULL as class_name');
            $selectArray[] = DB::raw('COALESCE(fs.total_amount, 0) as monthly_fee');
            $selectArray[] = DB::raw('COALESCE(fs.total_amount, 0) as total_amount');
            $selectArray[] = DB::raw('COALESCE(fs.balance_amount, COALESCE(fs.total_amount, 0) - COALESCE(fs.paid_amount, 0), 0) as remaining_amount');
        }

        if ($hasSectionId) {
            $selectArray[] = 'sections.name as section_name';
        } else {
            $selectArray[] = DB::raw('NULL as section_name');
        }

        // Common fee fields
        $selectArray = array_merge($selectArray, [
            DB::raw('0 as prev_amount'), // carry_forward column doesn't exist, using 0
            DB::raw('COALESCE(fs.discount_amount, 0) as discount_amount'),
            DB::raw('COALESCE(fs.fine_amount, 0) as late_fee'), // using fine_amount as late_fee
            DB::raw('COALESCE(fs.paid_amount, 0) as accumulated_paid'), // using paid_amount instead of partial_fee_paid
            DB::raw("COALESCE(fs.status, 'pending') as status") // using status instead of payment_status
        ]);

        $query = Student::query()->select($selectArray);

        // Apply merchant_id filter (CRITICAL: Always filter by merchant_id)
        $merchantId = $request->merchant_id 
            ?? $request->attributes->get('merchant_id') 
            ?? auth()->user()?->merchant_id 
            ?? null;
        
        // Always filter by merchant_id if available
        if ($merchantId) {
            $query->where('students.merchant_id', $merchantId);
        } else {
            // If merchant_id is not available, log warning and return empty result for security
            Log::warning('StudentController: merchant_id not found, filtering students', [
                'user_id' => auth()->id(),
                'request_path' => $request->path()
            ]);
            $query->whereRaw('1 = 0'); // Return no results if merchant_id is missing
        }

        // Only join classes if class_id column exists
        if ($hasClassId) {
            $query->leftJoin('classes', function ($join) use ($merchantId) {
                $join->on('students.class_id', '=', 'classes.id');
                if ($merchantId) {
                    $join->where('classes.merchant_id', '=', $merchantId);
                }
            });
            $query->leftJoin('fee_defaults as fd', function ($join) use ($merchantId) {
                $join->on('students.class_id', '=', 'fd.class_id');
                if ($merchantId) {
                    $join->where('fd.merchant_id', '=', $merchantId);
                }
            });
        }

        // Only join sections if section_id column exists
        if ($hasSectionId) {
            $query->leftJoin('sections', function ($join) use ($merchantId) {
                $join->on('students.section_id', '=', 'sections.id');
                if ($merchantId) {
                    $join->where('sections.merchant_id', '=', $merchantId);
                }
            });
        }

        $query->leftJoin('family_infos', function ($join) use ($merchantId) {
            $join->on('students.id', '=', 'family_infos.student_id');
            if ($merchantId) {
                $join->where('family_infos.merchant_id', '=', $merchantId);
            }
        })
        ->leftJoin('fee_summaries as fs', function ($join) use ($monthFor, $merchantId) {
            $join->on('students.id', '=', 'fs.student_id')
                 ->where('fs.month_for', '=', $monthFor);
            if ($merchantId) {
                $join->where('fs.merchant_id', '=', $merchantId);
            }
        });

        // Search filter - Search across all columns using ILIKE (case-insensitive, PostgreSQL)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $hasClassId, $hasSectionId) {
                // Basic student fields - ILIKE is already case-insensitive in PostgreSQL
                $q->where(DB::raw('students.first_name'), 'ILIKE', '%' . $search . '%')
                    ->orWhere(DB::raw('students.last_name'), 'ILIKE', '%' . $search . '%')
                    ->orWhere(DB::raw('COALESCE(students.first_name, \'\') || \' \' || COALESCE(students.last_name, \'\')'), 'ILIKE', '%' . $search . '%');
                
                // Search in roll_number if column exists
                if (Schema::hasColumn('students', 'roll_number')) {
                    $q->orWhere(DB::raw('CAST(students.roll_number AS TEXT)'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in admission_number if column exists
                if (Schema::hasColumn('students', 'admission_number')) {
                    $q->orWhere(DB::raw('students.admission_number'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in email if column exists
                if (Schema::hasColumn('students', 'email')) {
                    $q->orWhere(DB::raw('students.email'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in phone_number if column exists
                if (Schema::hasColumn('students', 'phone_number')) {
                    $q->orWhere(DB::raw('students.phone_number'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in class name if class_id exists
                if ($hasClassId) {
                    $q->orWhere(DB::raw('classes.name'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in section name if section_id exists
                if ($hasSectionId) {
                    $q->orWhere(DB::raw('sections.name'), 'ILIKE', '%' . $search . '%');
                }
                
                // Search in family father name
                $q->orWhere(DB::raw('family_infos.father_name'), 'ILIKE', '%' . $search . '%');
            });
        }
    
        // Class filter (only if class_id column exists)
        if ($request->filled('class_id') && $hasClassId) {
            $query->where('students.class_id', $request->class_id);
        }
    
        // Section filter (only if section_id column exists)
        if ($request->filled('section_id') && is_numeric($request->section_id) && $hasSectionId) {
            $query->where('students.section_id', $request->section_id);
        }

        if ($request->filled('fee_status') && $request->fee_status !== 'All') {
            $query->where(DB::raw("LOWER(COALESCE(fs.payment_status, 'unpaid'))"), strtolower($request->fee_status));
        }

        // if($request->filled('month')){
        //     $query->where()
        // }

        if($request->filled('roll_number')){
            $query->where('students.roll_number', 'like', "%{$request->roll_number}%");
        }
    
        // Date filter
        if ($request->filled('date')) {
            $query->whereDate('students.created_at', $request->input('date'));
        }
    
        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('students.first_name', $sortDirection)
                      ->orderBy('students.last_name', $sortDirection);
                break;
            case 'roll_number':
                $query->orderBy('students.roll_number', $sortDirection);
                break;
            case 'admission_date':
                $query->orderBy('students.admission_date', $sortDirection);
                break;
            default:
                $query->orderBy('students.first_name', 'asc')
                      ->orderBy('students.last_name', 'asc');
        }
    
        // Clone for gender counts
        $maleStudentsQuery = (clone $query)->where('students.gender', 'Male')->count();
        $femaleStudentsQuery = (clone $query)->where('students.gender', 'Female')->count();
    
        $perPage = $request->per_page ?? 10;
        $students = $query->paginate($perPage);
    
        // Map class name (already selected as class_name) - transform items
        foreach ($students->items() as $student) {
            $student->class = $student->class_name ?? '';
            $student->status = ucfirst($student->status);
            $student->remaining_amount = $student->status === 'Paid' ? 0 : $student->remaining_amount;
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Students fetched successfully.',
            'result' => $students,
            'total_male' => $maleStudentsQuery,
            'total_female' => $femaleStudentsQuery
        ]);
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = Student::create($request->validated());
            $student->merchant_id = $request->merchant_id;
            
            // Create related records if provided
            if ($request->has('contact_info')) {
                $student->contactInfo()->create($request->input('contact_info'));
            }
            
            if ($request->has('family_info')) {
                $student->familyInfo()->create($request->input('family_info'));
            }

            return response()->json([
                'status' => true,
                'message' => 'Student created successfully',
                'result' => $student->load(['class', 'contactInfo', 'familyInfo'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'Student created successfully',
            'result'  => $student->load(['class', 'contactInfo', 'familyInfo'])
        ]);
    }

    /**
     * Update the specified student in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $student->update($request->validated());
            
            // Update related records if provided
            if ($request->has('contact_info')) {
                $student->contactInfo()->update($request->input('contact_info'));
            }
            
            if ($request->has('family_info')) {
                $student->familyInfo()->update($request->input('family_info'));
            }

            return response()->json([
                'status' => true,
                'message' => 'Student updated successfully',
                'result' => $student->load(['class', 'contactInfo', 'familyInfo'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $student = Student::where('id', $request->id)->delete();
            return response()->json([
                'status' => true,
                'message' => "Student deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
