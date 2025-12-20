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

        $query = Student::query()
            ->select(
                'students.*',
                'classes.name as class_name',
                'sections.name as section_name',
                'family_infos.father_name as family_father_name',
                DB::raw('COALESCE(fs.base_fee, fd.monthly_fee, 0) as monthly_fee'),
                DB::raw('COALESCE(fs.carry_forward, 0) as prev_amount'),
                DB::raw('COALESCE(fs.discount_amount, 0) as discount_amount'),
                DB::raw('COALESCE(fs.late_fee, 0) as late_fee'),
                DB::raw('COALESCE(fs.final_amount, fd.monthly_fee, 0) as total_amount'),
                DB::raw('COALESCE(fs.partial_fee_paid, 0) as accumulated_paid'),
                DB::raw('COALESCE(fs.final_amount, fd.monthly_fee, 0) - COALESCE(fs.partial_fee_paid, 0) as remaining_amount'),
                DB::raw("COALESCE(fs.payment_status, 'unpaid') as status")
            )
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->leftJoin('family_infos', 'students.id', '=', 'family_infos.student_id')
            ->leftJoin('fee_defaults as fd', 'students.class_id', '=', 'fd.class_id')
            ->leftJoin('fee_summaries as fs', function ($join) use ($monthFor) {
                $join->on('students.id', '=', 'fs.student_id')
                     ->where('fs.month_for', '=', $monthFor);
            });

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('students.first_name', 'like', "%{$search}%")
                    ->orWhere('students.last_name', 'like', "%{$search}%")
                    ->orWhere('students.roll_number', 'like', "%{$search}%");
            });
        }
    
        // Class filter
        if ($request->filled('class_id')) {
            $query->where('students.class_id', $request->class_id);
        }
    
        // Section filter
        if ($request->filled('section_id') && is_numeric($request->section_id)) {
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
    
        // Map class name (already selected as class_name)
        $students->getCollection()->transform(function ($student) {
            $student->class = $student->class_name ?? '';
            $student->status = ucfirst($student->status);
            $student->remaining_amount = $student->status === 'Paid' ? 0 : $student->remaining_amount;
            return $student;
        });
    
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
