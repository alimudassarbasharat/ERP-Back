<?php

namespace App\Http\Controllers\FeeManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeSummary;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\FeeManagement\StorePartialFeePaymentRequest;

class FeePaymentController extends Controller
{
    /**
     * Store a fee payment and update the fee summary.
     */
    public function store(StorePartialFeePaymentRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Step 1: Find or create the fee summary record
            $feeSummary = FeeSummary::firstOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'class_id' => $validated['class_id'],
                    'month_for' => $validated['month_for'],
                ],
                [
                    'base_fee' => (int) $validated['base_fee'],
                    'final_amount' => (int) $validated['final_amount'],
                    'partial_fee_paid' => 0, // Start with 0 paid amount
                    'payment_status' => 'unpaid',
                    'due_date' => Carbon::parse($validated['month_for'])->endOfMonth()->toDateString(),
                ]
            );

            // Step 2: Create a record in the fee_payments table for this transaction
            $feePayment = FeePayment::create([
                'fee_summary_id' => $feeSummary->id,
                'amount_paid' => (int) $validated['amount_paid'], // Store as integer
                'payment_date' => $validated['payment_date'],
                'payment_mode' => $validated['payment_mode'],
                'received_by' => $validated['received_by'],
                'receipt_no' => 'FEE-' . date('Ymd') . '-' . mt_rand(1000, 9999),
                'remarks' => $validated['remarks'],
            ]);

            // Step 3: Calculate total paid amount from all payments for this fee summary
            $totalPaidFromPayments = FeePayment::where('fee_summary_id', $feeSummary->id)->sum('amount_paid');
            
            // Step 4: Update partial_fee_paid with accumulated paid amount
            $accumulatedPaidAmount = (int) $totalPaidFromPayments;
            
            // Step 5: Calculate remaining amount (total fee - accumulated paid amount)
            $remainingAmount = (int) $feeSummary->final_amount - $accumulatedPaidAmount;
            
            // Ensure remaining amount is not negative
            $remainingAmount = max(0, $remainingAmount);
            
            // Step 6: Determine payment status
            if ($remainingAmount <= 0) {
                $payment_status = 'paid';
                $remainingAmount = 0; // No remaining amount
            } else {
                $payment_status = 'partial';
            }
            
            // Step 7: Update the fee summary with accumulated paid amount and remaining amount
            $feeSummary->update([
                'partial_fee_paid' => $accumulatedPaidAmount, // Store accumulated paid amount as integer
                'payment_status' => $payment_status,
            ]);
            
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Partial payment saved successfully.',
                'result' => [
                    'fee_payment' => $feePayment,
                    'fee_summary' => $feeSummary->fresh(),
                    'total_paid' => $accumulatedPaidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_count' => FeePayment::where('fee_summary_id', $feeSummary->id)->count(),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to save payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a full fee payment and update the fee summary.
     */
    public function fullPayment(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'class_id' => 'required|integer',
            'month_for' => 'required|string',
            'base_fee' => 'required|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_mode' => 'required',
            'received_by' => 'nullable',
            'remarks' => 'nullable|string|max:500',
        ], [
            'student_id.required' => 'Student ID is required.',
            'student_id.integer' => 'Student ID must be a valid number.',
            'class_id.required' => 'Class ID is required.',
            'class_id.integer' => 'Class ID must be a valid number.',
            'month_for.required' => 'Month is required.',
            'base_fee.required' => 'Base fee is required.',
            'base_fee.numeric' => 'Base fee must be a valid number.',
            'base_fee.min' => 'Base fee cannot be negative.',
            'final_amount.required' => 'Final amount is required.',
            'final_amount.numeric' => 'Final amount must be a valid number.',
            'final_amount.min' => 'Final amount cannot be negative.',
            'amount_paid.required' => 'Amount paid is required.',
            'amount_paid.numeric' => 'Amount paid must be a valid number.',
            'amount_paid.min' => 'Amount paid cannot be negative.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'payment_mode.required' => 'Payment mode is required.',
            'payment_mode.in' => 'Payment mode must be Cash, Cheque, Online, or Bank Transfer.',
            'received_by.required' => 'Received by field is required.',
            'remarks.max' => 'Remarks cannot exceed 500 characters.',
        ]);

        DB::beginTransaction();
        try {
            // Step 1: Find or create the fee summary record
            $feeSummary = FeeSummary::firstOrCreate(
                [
                    'student_id' => $request->student_id,
                    'class_id' => $request->class_id,
                    'month_for' => $request->month_for,
                ],
                [
                    'base_fee' => (int) $request->base_fee,
                    'final_amount' => (int) $request->final_amount,
                    'partial_fee_paid' => 0, // No partial payment for full payment
                    'payment_status' => 'unpaid',
                    'due_date' => Carbon::parse($request->month_for)->endOfMonth()->toDateString(),
                ]
            );

            // Step 2: Create a record in the fee_payments table for this transaction
            $feePayment = FeePayment::create([
                'fee_summary_id' => $feeSummary->id,
                'amount_paid' => (int) $request->amount_paid, // Use the actual amount being paid
                'payment_date' => $request->payment_date,
                'payment_mode' => $request->payment_mode,
                'received_by' => auth()->id(),
                'receipt_no' => 'FEE-' . date('Ymd') . '-' . mt_rand(1000, 9999),
                'remarks' => $request->remarks,
            ]);

            // Step 3: Update the fee_summaries table for full payment
            $feeSummary->update([
                'final_amount' => (int) $request->final_amount, // Keep original total fee
                'partial_fee_paid' => 0, // No remaining amount for full payment
                'payment_status' => 'paid', // Mark as paid
            ]);
            
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Full payment saved successfully.',
                'result' => [
                    'fee_payment' => $feePayment,
                    'fee_summary' => $feeSummary->fresh(),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to save full payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for a specific student.
     */
    public function paymentHistory($studentId)
    {
        try {
            // Get student information
            $student = Student::with(['class', 'section', 'familyInfo'])
                ->find($studentId);

            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student not found.',
                    'result' => null
                ]);
            }

            // Get fee summary for the student
            $feeSummary = FeeSummary::where('student_id', $studentId)
                ->with(['payments' => function($query) {
                    $query->orderBy('payment_date', 'desc');
                }])
                ->first();

            // Get all payments for this student
            $payments = FeePayment::whereHas('feeSummary', function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with('feeSummary')
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function($payment) {
                return [
                    'amount_paid' => $payment->amount_paid ?? 0,
                    'payment_date' => $payment->payment_date ?? '',
                    'payment_mode' => $payment->payment_mode ?? '',
                    'remarks' => $payment->remarks ?? '',
                    'received_by_name' => $payment->received_by ?? '',
                    'receipt_no' => $payment->receipt_no ?? '',
                    'created_at' => $payment->created_at,
                ];
            });

            // Calculate totals from backend
            $totalPaid = $payments->sum('amount_paid');
            $totalDue = $feeSummary ? ($feeSummary->final_amount - $totalPaid) : 0;

            // Prepare student info for frontend
            $studentInfo = [
                'grNo' => $student->id,
                'name' => $student->first_name . ' ' . ($student->last_name ?? ''),
                'classSection' => ($student->class ? $student->class->name : '') . ' / ' . ($student->section ? $student->section->name : ''),
                'fatherName' => $student->familyInfo ? $student->familyInfo->father_name : '',
                'contactEmail' => $student->contact_email ?? '',
                'class_id' => $student->class_id,
                'section_id' => $student->section_id,
            ];

            // Prepare fee summary data
            $feeSummaryData = null;
            if ($feeSummary) {
                $feeSummaryData = [
                    'student_id' => $feeSummary->student_id,
                    'class_id' => $feeSummary->class_id,
                    'month_for' => $feeSummary->month_for,
                    'base_fee' => $feeSummary->base_fee ?? 0,
                    'discount_amount' => $feeSummary->discount_amount ?? 0,
                    'late_fee' => $feeSummary->late_fee ?? 0,
                    'carry_forward' => $feeSummary->carry_forward ?? 0,
                    'final_amount' => $feeSummary->final_amount ?? 0,
                    'partial_fee_paid' => $feeSummary->partial_fee_paid ?? 0,
                    'payment_status' => $feeSummary->payment_status ?? 'unpaid',
                    'due_date' => $feeSummary->due_date,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $totalDue),
                    'remaining_amount' => max(0, $totalDue),
                ];
            } else {
                // If no fee summary exists, create default data
                $feeSummaryData = [
                    'student_id' => $student->id,
                    'class_id' => $student->class_id,
                    'month_for' => now()->format('Y-m-01'),
                    'base_fee' => 0,
                    'discount_amount' => 0,
                    'late_fee' => 0,
                    'carry_forward' => 0,
                    'final_amount' => 0,
                    'partial_fee_paid' => 0,
                    'payment_status' => 'unpaid',
                    'due_date' => now()->endOfMonth()->format('Y-m-d'),
                    'total_paid' => 0,
                    'total_due' => 0,
                    'remaining_amount' => 0,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment history fetched successfully.',
                'result' => [
                    'student_info' => $studentInfo,
                    'fee_summary' => $feeSummaryData,
                    'payments' => $payments,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $totalDue),
                    'payment_count' => $payments->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch payment history.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 