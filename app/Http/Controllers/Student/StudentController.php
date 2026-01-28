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
        // -------------------- MONTH HANDLING --------------------
        $challanMonth = $request->input('challan_month');

        if ($challanMonth) {
            try {
                $monthFor = Carbon::createFromFormat('F Y', $challanMonth)->startOfMonth();
            } catch (\Exception $e) {
                $monthFor = now()->startOfMonth();
            }
        } else {
            $monthFor = null; // no month filter
        }

        // -------------------- BASE QUERY --------------------
        $query = Student::with([
            'class',
            'section',
            'familyInfo',
            'feeDiscounts',
            'feePlans',
            'feeInvoices' => function ($q) use ($monthFor) {
                if ($monthFor) {
                    // Filter invoices by month if provided
                    $q->whereMonth('invoice_date', $monthFor->month)
                        ->whereYear('invoice_date', $monthFor->year);
                }
            }
        ]);

        // -------------------- SEARCH FILTER --------------------
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%$search%")
                    ->orWhere('last_name', 'ILIKE', "%$search%")
                    ->orWhere(DB::raw("COALESCE(first_name,'') || ' ' || COALESCE(last_name,'')"), 'ILIKE', "%$search%");
            });
        }

        // -------------------- OTHER FILTERS --------------------
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('roll_number')) {
            $query->where('roll_number', 'ILIKE', "%{$request->roll_number}%");
        }

        if ($request->filled('fee_status') && $request->fee_status !== 'All') {
            $query->whereHas('feeInvoices', function ($q) use ($request, $monthFor) {
                if ($monthFor) {
                    $q->whereMonth('invoice_date', $monthFor->month)
                        ->whereYear('invoice_date', $monthFor->year);
                }
                $q->where('status', $request->fee_status);
            });
        }

        // -------------------- SORTING --------------------
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if ($sortBy === 'roll_number') {
            $query->orderBy('roll_number', $sortDirection);
        } elseif ($sortBy === 'admission_date') {
            $query->orderBy('admission_date', $sortDirection);
        } else {
            $query->orderBy('first_name', $sortDirection)
                ->orderBy('last_name', $sortDirection);
        }

        // -------------------- GENDER COUNTS --------------------
        $maleCount = (clone $query)->where('gender', 'Male')->count();
        $femaleCount = (clone $query)->where('gender', 'Female')->count();

        // -------------------- PAGINATION --------------------
        $perPage = $request->per_page ?? 10;
        $students = $query->paginate($perPage);

        // -------------------- TRANSFORM RESULTS --------------------
        $students->getCollection()->transform(function ($student) use ($monthFor) {
            // Pick the relevant invoice
            $feeInvoice = $monthFor
                ? $student->feeInvoices->first() // invoice for selected month
                : $student->feeInvoices->sortByDesc('invoice_date')->first(); // latest invoice if no month

            $feeDiscount = $student->feeDiscounts->sum('amount');
            $feePlan = $student->feePlans->sum('amount');

            $totalAmount = $feeInvoice->total_amount ?? $feePlan ?? 0;
            $paidAmount = $feeInvoice->paid_amount ?? 0;
            $balanceAmount = $totalAmount - $paidAmount;

            return [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'roll_number' => $student->roll_number,
                'admission_date' => $student->admission_date,
                'class_name' => $student->class->name ?? null,
                'section_name' => $student->section->name ?? null,
                'father_name' => $student->familyInfo->father_name ?? null,
                'monthly_fee' => $totalAmount,
                'total_amount' => $totalAmount,
                'remaining_amount' => $balanceAmount,
                'discount_amount' => $feeDiscount,
                'status' => ucfirst($feeInvoice->status ?? 'pending'),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Students fetched successfully',
            'result' => $students,
            'total_male' => $maleCount,
            'total_female' => $femaleCount,
        ]);
    }

    /**
     * Store a newly created student in storage.
     */

    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Extract relations
            $contactInfo = $data['contact_info'] ?? null;
            $familyInfo  = $data['family_info'] ?? null;

            unset($data['contact_info'], $data['family_info']);

            $data['merchant_id'] = $request->merchant_id;

            $student = Student::create($data);

            if ($contactInfo) {
                $student->contactInfo()->create($contactInfo + ['merchant_id' => $request->merchant_id]);
            }

            if ($familyInfo) {
                $student->familyInfo()->create($familyInfo + ['merchant_id' => $request->merchant_id]);
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
            $data = $request->validated();

            $contactInfo = $data['contact_info'] ?? null;
            $familyInfo  = $data['family_info'] ?? null;

            unset($data['contact_info'], $data['family_info']);


            $student->update($data);


            if ($contactInfo) {
                $student->contactInfo()->update($contactInfo + ['merchant_id' => $request->merchant_id]);
            }

            if ($familyInfo) {
                $student->familyInfo()->update($familyInfo + ['merchant_id' => $request->merchant_id]);
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
    public function destroy(Request $request, Student $student): JsonResponse
    {

        // dd($request->all(), $student);
        try {
            // $student = Student::where('id', $request->id)->delete();
            $student->delete();
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
