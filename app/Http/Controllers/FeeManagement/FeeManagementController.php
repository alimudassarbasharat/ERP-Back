<?php

namespace App\Http\Controllers\FeeManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeDefault;
use App\Http\Requests\FeeManagement\StoreFeeDefaultRequest;
use Illuminate\Support\Facades\DB;
use App\Models\LateFee;
use App\Models\Classes;
use App\Models\Section;
use Carbon\Carbon;

class FeeManagementController extends Controller
{
    public function store(StoreFeeDefaultRequest $request)
    {
        $feeDefault = FeeDefault::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fee default created successfully',
            'data'    => $feeDefault
        ]);
    }

    public function update(StoreFeeDefaultRequest $request, $id)
    {
        $feeDefault = FeeDefault::findOrFail($id);
        $feeDefault->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Fee default updated successfully',
            'data'    => $feeDefault
        ]);
    }

    public function destroy($id)
    {
        $feeDefault = FeeDefault::findOrFail($id);
        $feeDefault->delete();
        return response()->json([
            'success' => true,
            'message' => 'Fee default deleted successfully'
        ]);
    }

    public function index(Request $request)
    {
        $query = DB::table('fee_defaults')
            ->select('fee_defaults.*', 'classes.name as class_name')
            ->leftJoin('classes', 'fee_defaults.class_id', 'classes.id');

        // Search filter (by class name or monthly_fee)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('classes.name', 'like', "%{$search}%")
                  ->orWhere('fee_defaults.monthly_fee', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('fee_defaults.status', $request->status);
        }

        // Class filter
        if ($request->filled('class_id')) {
            $query->where('fee_defaults.class_id', $request->class_id);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->per_page ?? 10;
        $feeDefaults = $query->paginate($perPage);

        // Map class name
        foreach ($feeDefaults as $fee) {
            $fee->class = $fee->class_name ?? '';
        }

        return response()->json([
            'status' => true,
            'message' => 'Fee defaults fetched successfully.',
            'result' => $feeDefaults,
            'total' => $feeDefaults->total(),
        ]);
    }

    // --- LATE FEE CRUD ---
    public function lateFeeIndex(Request $request)
    {
        $query = LateFee::query();
        if ($request->filled('merchant_id')) {
            $merchantId = $request->merchant_id;
            // Only filter by merchant_id if it's numeric (bigint column)
            // Skip filter for string merchant_ids like "SUPER123"
            if (is_numeric($merchantId)) {
                $query->where('merchant_id', (int)$merchantId);
            }
            // For non-numeric merchant_ids, return all records or handle as needed
        }
        $lateFees = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 10);
        return response()->json([
            'success' => true,
            'message' => 'Late fees fetched successfully.',
            'data' => $lateFees
        ]);
    }

    public function lateFeeStore(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|integer',
            'late_fee_amount' => 'required|numeric|min:0',
            'apply_after_days' => 'required|integer|min:1',
            'is_auto_apply' => 'required|boolean',
            'added_by' => 'required|integer',
        ]);
        $lateFee = LateFee::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Late fee created successfully.',
            'data' => $lateFee
        ]);
    }

    public function lateFeeShow($id)
    {
        $lateFee = LateFee::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $lateFee
        ]);
    }

    public function lateFeeUpdate(Request $request, $id)
    {
        $lateFee = LateFee::findOrFail($id);
        $validated = $request->validate([
            'merchant_id' => 'required|integer',
            'late_fee_amount' => 'required|numeric|min:0',
            'apply_after_days' => 'required|integer|min:1',
            'is_auto_apply' => 'required|boolean',
            'added_by' => 'required|integer',
        ]);
        $lateFee->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Late fee updated successfully.',
            'data' => $lateFee
        ]);
    }

    public function lateFeeDestroy($id)
    {
        $lateFee = LateFee::findOrFail($id);
        $lateFee->delete();
        return response()->json([
            'success' => true,
            'message' => 'Late fee deleted successfully.'
        ]);
    }

    public function classWiseFeeSummary(Request $request)
    {
        $month = $request->input('month'); // optional filter
        $session = $request->input('session'); // optional filter
        
        // Convert month name to date format if provided
        $monthForFilter = null;
        if ($month) {
            try {
                $monthNumber = date('m', strtotime($month));
                $year = date('Y'); // Use current year or extract from session
                $monthForFilter = $year . '-' . str_pad($monthNumber, 2, '0', STR_PAD_LEFT) . '-01';
            } catch (\Exception $e) {
                // If month conversion fails, ignore the filter
                $monthForFilter = null;
            }
        }

        $classes = Classes::with(['students' => function($q) {
            $q->whereNull('deleted_at');
        }])->get();

        $result = $classes->map(function($class) use ($monthForFilter, $session) {
            // Get actual students count in this class
            $actualStudents = $class->students()->whereNull('deleted_at')->count();
            
            $feeSummaries = \App\Models\FeeSummary::where('class_id', $class->id)
                ->when($monthForFilter, function($q) use ($monthForFilter) {
                    $q->where('month_for', $monthForFilter);
                })
                // ->when($session, function($q) use ($session) {
                //     $q->whereHas('class.session', function($query) use($session){
                //         $query->where('name', (int)$session);
                //     });
                // })
                ->where('section_id', $class->section_id)
                ->get();

            $total_students = $actualStudents; // Use actual student count
            $total_fees = $feeSummaries->sum('final_amount');   
            $paid_count = $feeSummaries->where('payment_status', 'paid')->count();
            $unpaid_count = $feeSummaries->where('payment_status', '!=', 'paid')->count();

            // Calculate total paid and remaining fees
            $total_paid_fees = $feeSummaries->where('payment_status', 'paid')->sum('final_amount');
            $total_remaining_fees = $feeSummaries->where('payment_status', '!=', 'paid')->sum('final_amount');

            // Status logic
            if ($unpaid_count == 0 && $paid_count > 0) {
                $status = 'Complete';
            } elseif ($paid_count > 0) {
                $status = 'Partial';
            } else {
                $status = 'Unpaid';
            }

            $section_id = $class->section_id ?? null;
            $section_name = null;
            if (!empty($class->section_id)) {
                $section_name = Section::find($class->section_id)?->name;
            }

            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'section_id' => $section_id,
                'section_name' => $section_name,
                'total_fees' => $total_fees,
                'total_students' => $total_students,
                'paid_students' => $paid_count,
                'remaining_students' => $unpaid_count,
                'total_paid_fees' => $total_paid_fees,
                'total_remaining_fees' => $total_remaining_fees,
                'status' => $status,
            ];
        });

        return response()->json([
            'status' => true,
            'result' => $result
        ]);
    }

    public function familyWiseFeeSummary(Request $request)
    {
        $challanMonth = $request->input('month', now()->format('F Y'));
        // Convert 'June 2025' to '2025-06-01' for Postgres date comparison
            try {
            $monthFor = Carbon::createFromFormat('F Y', $challanMonth)->format('Y-m-01');
            } catch (\Exception $e) {
            $monthFor = now()->format('Y-m-01');
        }

        $query = DB::table('family_infos')
            ->select(
                'family_infos.id as family_id',
                'family_infos.father_name',
                'family_infos.mother_name',
                'family_infos.father_cnic as contact_number',
                'family_infos.home_address as address',
                'family_infos.merchant_id as family_code',
                DB::raw('COUNT(DISTINCT students.id) as children_count'),
                DB::raw('SUM(COALESCE(fs.base_fee, fd.monthly_fee, 0)) as total_monthly_fee'),
                DB::raw('SUM(COALESCE(fs.carry_forward, 0)) as total_previous_dues'),
                DB::raw('SUM(COALESCE(fs.discount_amount, 0)) as total_discount'),
                DB::raw('SUM(COALESCE(fs.late_fee, 0)) as total_late_fee'),
                DB::raw('SUM(COALESCE(fs.final_amount, fd.monthly_fee, 0)) as total_amount'),
                DB::raw('SUM(COALESCE(fs.partial_fee_paid, 0)) as total_paid'),
                DB::raw('SUM(COALESCE(fs.final_amount, fd.monthly_fee, 0) - COALESCE(fs.partial_fee_paid, 0)) as total_remaining'),
                DB::raw("COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) as paid_students"),
                DB::raw("COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') != 'paid' THEN 1 END) as unpaid_students"),
                DB::raw("CASE 
                    WHEN COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') != 'paid' THEN 1 END) = 0 
                         AND COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) > 0 
                    THEN 'paid'
                    WHEN COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) > 0 
                    THEN 'partial'
                    ELSE 'unpaid'
                END as family_status")
            )
            ->leftJoin('students', 'family_infos.student_id', '=', 'students.id')
            ->leftJoin('fee_defaults as fd', 'students.class_id', '=', 'fd.class_id')
            ->leftJoin('fee_summaries as fs', function ($join) use ($monthFor) {
                $join->on('students.id', '=', 'fs.student_id')
                     ->where('fs.month_for', '=', $monthFor);
            })
            ->whereNull('students.deleted_at')
            ->groupBy(
                'family_infos.id',
                'family_infos.father_name',
                'family_infos.mother_name',
                'family_infos.father_cnic',
                'family_infos.home_address',
                'family_infos.merchant_id'
            )
            ->havingRaw('COUNT(DISTINCT students.id) > 0');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('family_infos.father_name', 'like', "%{$search}%")
                    ->orWhere('family_infos.mother_name', 'like', "%{$search}%")
                    ->orWhere('family_infos.merchant_id', 'like', "%{$search}%");
            });
        }

        // Fee status filter
        if ($request->filled('fee_status') && $request->fee_status !== 'All') {
            $status = strtolower($request->fee_status);
            if ($status === 'paid') {
                $query->havingRaw("COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') != 'paid' THEN 1 END) = 0 
                                   AND COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) > 0");
            } elseif ($status === 'partial') {
                $query->havingRaw("COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) > 0 
                                   AND COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') != 'paid' THEN 1 END) > 0");
            } elseif ($status === 'unpaid') {
                $query->havingRaw("COUNT(CASE WHEN COALESCE(fs.payment_status, 'unpaid') = 'paid' THEN 1 END) = 0");
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'fatherName');
        $sortDirection = $request->input('sort_direction', 'asc');
        switch ($sortBy) {
            case 'fatherName':
                $query->orderBy('family_infos.father_name', $sortDirection);
                break;
            case 'familyCode':
                $query->orderBy('family_infos.merchant_id', $sortDirection);
                break;
            case 'childrenCount':
                $query->orderBy('children_count', $sortDirection);
                break;
            case 'totalAmount':
                $query->orderBy('total_amount', $sortDirection);
                break;
            case 'totalPaid':
                $query->orderBy('total_paid', $sortDirection);
                break;
            case 'remainingAmount':
                $query->orderBy('total_remaining', $sortDirection);
                break;
            default:
                $query->orderBy('family_infos.father_name', 'asc');
        }

        $perPage = $request->per_page ?? 20;
        $families = $query->paginate($perPage);

        // Transform the collection to match frontend expectations
        $transformedData = collect($families->items())->map(function ($family) {
            return [
                'family_id' => $family->family_id,
                'family_code' => $family->family_code ?? 'FAM' . str_pad($family->family_id, 3, '0', STR_PAD_LEFT),
                'father_name' => $family->father_name ?? 'N/A',
                'mother_name' => $family->mother_name ?? 'N/A',
                'contact_number' => $family->contact_number ?? 'N/A',
                'address' => $family->address ?? 'N/A',
                'children_count' => $family->children_count ?? 0,
                'total_balance' => $family->total_previous_dues ?? 0,
                'monthly_fees' => $family->total_monthly_fee ?? 0,
                'total_discount' => $family->total_discount ?? 0,
                'total_late_fee' => $family->total_late_fee ?? 0,
                'total_amount' => $family->total_amount ?? 0,
                'total_paid' => $family->total_paid ?? 0,
                'remaining_amount' => max(0, $family->total_remaining ?? 0),
                'paid_students' => $family->paid_students ?? 0,
                'unpaid_students' => $family->unpaid_students ?? 0,
                'status' => ucfirst($family->family_status ?? 'unpaid'),
            ];
        });

        // Create response with pagination metadata
        $response = [
            'current_page' => $families->currentPage(),
            'data' => $transformedData,
            'first_page_url' => $families->url(1),
            'from' => $families->firstItem(),
            'last_page' => $families->lastPage(),
            'last_page_url' => $families->url($families->lastPage()),
            'next_page_url' => $families->nextPageUrl(),
            'path' => $families->path(),
            'per_page' => $families->perPage(),
            'prev_page_url' => $families->previousPageUrl(),
            'to' => $families->lastItem(),
            'total' => $families->total()
        ];

        return response()->json([
            'status' => true,
            'message' => 'Family fee summaries fetched successfully.',
            'result' => $response
        ]);
    }

    public function payClassFees(Request $request, $classId)
    {
        $request->validate([
            'payment_mode' => 'required|string|in:cash,bank_transfer,cheque,online',
            'payment_date' => 'required|date',
            'received_by' => 'nullable',
            'remarks' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Get all students in this class
            $students = \App\Models\Student::where('class_id', $classId)
                ->whereNull('deleted_at')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No students found in this class.'
                ], 404);
            }

            $successCount = 0;
            $failCount = 0;
            $results = [];
            $monthFor = now()->format('Y-m-01');

            foreach ($students as $student) {
                try {
                    // Get or create fee summary
                    $feeSummary = \App\Models\FeeSummary::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'class_id' => $classId,
                            'month_for' => $monthFor,
                        ],
                        [
                            'base_fee' => $student->class->feeDefault->monthly_fee ?? 1000,
                            'final_amount' => $student->class->feeDefault->monthly_fee ?? 1000,
                            'partial_fee_paid' => 0,
                            'payment_status' => 'unpaid',
                            'due_date' => now()->endOfMonth()->toDateString(),
                        ]
                    );

                    // Skip if already paid
                    if ($feeSummary->payment_status === 'paid') {
                        continue;
                    }

                    // Calculate remaining amount
                    $remainingAmount = $feeSummary->final_amount - $feeSummary->partial_fee_paid;

                    if ($remainingAmount > 0) {
                        // Create payment record
                        $feePayment = \App\Models\FeePayment::create([
                            'fee_summary_id' => $feeSummary->id,
                            'amount_paid' => $remainingAmount,
                            'payment_date' => $request->payment_date,
                            'payment_mode' => $request->payment_mode,
                            'received_by' => Auth()->id(),
                            'receipt_no' => 'FEE-' . date('Ymd') . '-' . mt_rand(1000, 9999),
                            'remarks' => $request->remarks ?? "Class-wise bulk payment for {$student->first_name} {$student->last_name}",
                        ]);

                        // Update fee summary
                        $feeSummary->update([
                            'partial_fee_paid' => (int)$feeSummary->final_amount,
                            'payment_status' => 'paid',
                        ]);

                        $results[] = [
                            'student_id' => $student->id,
                            'student_name' => $student->first_name . ' ' . $student->last_name,
                            'amount_paid' => $remainingAmount,
                            'receipt_no' => $feePayment->receipt_no,
                            'status' => 'success'
                        ];

                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    $failCount++;
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Class fees paid successfully. {$successCount} students paid, {$failCount} failed.",
                'result' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'details' => $results
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to pay class fees.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFamilyStudents(Request $request, $familyId)
    {
        try {
            // Get family info
            $family = DB::table('family_infos')
                ->where('id', $familyId)
                ->first();

            if (!$family) {
                return response()->json([
                    'status' => false,
                    'message' => 'Family not found.'
                ], 404);
            }

            // Get all students in this family
            $students = DB::table('students')
                ->select(
                    'students.id',
                    'students.first_name',
                    'students.last_name',
                    'students.roll_number',
                    'students.contact_email',
                    'classes.name as class_name',
                    'sections.name as section_name',
                    'fs.base_fee',
                    'fs.final_amount',
                    'fs.partial_fee_paid',
                    'fs.discount_amount',
                    'fs.late_fee',
                    'fs.carry_forward',
                    'fs.payment_status',
                    'fs.due_date',
                    'fd.monthly_fee as default_fee'
                )
                ->leftJoin('family_infos', 'students.id', '=', 'family_infos.student_id')
                ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
                ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                ->leftJoin('fee_summaries as fs', function($join) {
                    $join->on('students.id', '=', 'fs.student_id')
                         ->where('fs.month_for', '=', now()->format('Y-m-01'));
                })
                ->leftJoin('fee_defaults as fd', 'students.class_id', '=', 'fd.class_id')
                ->where('family_infos.id', $familyId)
                ->whereNull('students.deleted_at')
                ->get();

            $studentsData = $students->map(function($student) {
                $baseFee = $student->base_fee ?? $student->default_fee ?? 1000;
                $finalAmount = $student->final_amount ?? $baseFee;
                $paidAmount = $student->partial_fee_paid ?? 0;
                $remainingAmount = $finalAmount - $paidAmount;

                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . ($student->last_name ?? ''),
                    'roll_number' => $student->roll_number,
                    'class_section' => ($student->class_name ?? 'N/A') . ' - ' . ($student->section_name ?? 'N/A'),
                    'contact_email' => $student->contact_email ?? 'N/A',
                    'base_fee' => $baseFee,
                    'discount_amount' => $student->discount_amount ?? 0,
                    'late_fee' => $student->late_fee ?? 0,
                    'carry_forward' => $student->carry_forward ?? 0,
                    'final_amount' => $finalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => max(0, $remainingAmount),
                    'payment_status' => $student->payment_status ?? 'unpaid',
                    'due_date' => $student->due_date ?? now()->endOfMonth()->format('Y-m-d'),
                ];
            });

            return response()->json([
                'status' => true,
                'result' => [
                    'family' => [
                        'id' => $family->id,
                        'father_name' => $family->father_name,
                        'mother_name' => $family->mother_name,
                        'contact_number' => $family->father_cnic,
                        'address' => $family->home_address,
                        'family_code' => $family->merchant_id,
                    ],
                    'students' => $studentsData,
                    'students_count' => $studentsData->count(),
                    'total_amount' => $studentsData->sum('final_amount'),
                    'total_paid' => $studentsData->sum('paid_amount'),
                    'total_remaining' => $studentsData->sum('remaining_amount'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch family students.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 