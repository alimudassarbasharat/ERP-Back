<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeInvoice;
use App\Models\Challan;
use App\Models\FeePayment;
use App\Models\FeeSummary;
use App\Models\Student;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FeeManagementController extends Controller
{
    /**
     * Get fee management dashboard summary (lightweight, cached)
     * Owner-first: Shows what owner needs to know in < 10 seconds
     */
    public function getSummary(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $month = $request->input('month', now()->format('Y-m'));
        
        // Cache for 5 minutes
        $cacheKey = "fee_summary_{$schoolId}_{$month}";
        
        $summary = Cache::remember($cacheKey, 300, function () use ($schoolId, $month) {
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = Carbon::parse($month . '-01')->endOfMonth();
            
            // Try to use FeeInvoice if table exists, otherwise use FeeSummary
            if (DB::getSchemaBuilder()->hasTable('fee_invoices')) {
                $invoices = FeeInvoice::where('school_id', $schoolId)
                    ->whereBetween('due_date', [$startDate, $endDate])
                    ->get();
                
                $expectedThisMonth = $invoices->sum('total_amount');
                $collected = $invoices->where('status', 'paid')->sum('total_amount');
                $pending = $invoices->where('status', 'unpaid')->sum('total_amount');
                $overdue = $invoices->where('status', 'unpaid')
                    ->filter(function ($invoice) {
                        return Carbon::parse($invoice->due_date)->isPast();
                    })
                    ->sum('total_amount');
                $overdueCount = $invoices->where('status', 'unpaid')
                    ->filter(function ($invoice) {
                        return Carbon::parse($invoice->due_date)->isPast();
                    })
                    ->count();
            } else {
                // Fallback to FeeSummary
                $summaries = FeeSummary::where('school_id', $schoolId)
                    ->where('month_for', $startDate->format('Y-m-d'))
                    ->get();
                
                $expectedThisMonth = $summaries->sum('final_amount');
                $collected = $summaries->where('payment_status', 'paid')->sum('final_amount');
                $pending = $summaries->where('payment_status', '!=', 'paid')->sum('final_amount');
                $overdue = $summaries->where('payment_status', '!=', 'paid')
                    ->filter(function ($summary) {
                        return $summary->due_date && Carbon::parse($summary->due_date)->isPast();
                    })
                    ->sum('final_amount');
                $overdueCount = $summaries->where('payment_status', '!=', 'paid')
                    ->filter(function ($summary) {
                        return $summary->due_date && Carbon::parse($summary->due_date)->isPast();
                    })
                    ->count();
            }
            
            // Get disputes count (if disputes table exists)
            $disputes = 0;
            if (DB::getSchemaBuilder()->hasTable('fee_disputes')) {
                $disputes = DB::table('fee_disputes')
                    ->where('school_id', $schoolId)
                    ->whereIn('status', ['raised', 'reviewing'])
                    ->count();
            }
            
            // Calculate trends (compare with previous month)
            $prevMonth = Carbon::parse($month . '-01')->subMonth();
            $prevInvoices = FeeInvoice::where('school_id', $schoolId)
                ->whereBetween('due_date', [$prevMonth->startOfMonth(), $prevMonth->copy()->endOfMonth()])
                ->get();
            
            $prevCollected = $prevInvoices->where('status', 'paid')->sum('total_amount');
            $prevPending = $prevInvoices->where('status', 'unpaid')->sum('total_amount');
            
            $collectedTrend = $prevCollected > 0 
                ? round((($collected - $prevCollected) / $prevCollected) * 100, 1)
                : 0;
            
            $pendingTrend = $prevPending > 0
                ? round((($pending - $prevPending) / $prevPending) * 100, 1)
                : 0;
            
            return [
                'expected_this_month' => $expectedThisMonth,
                'collected' => $collected,
                'pending' => $pending,
                'overdue' => $overdue,
                'overdue_count' => $overdueCount,
                'disputes' => $disputes,
                'collected_trend' => [
                    'type' => $collectedTrend >= 0 ? 'positive' : 'negative',
                    'value' => ($collectedTrend >= 0 ? '+' : '') . $collectedTrend . '%'
                ],
                'pending_trend' => [
                    'type' => $pendingTrend <= 0 ? 'positive' : 'negative',
                    'value' => ($pendingTrend >= 0 ? '+' : '') . $pendingTrend . '%'
                ]
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
    
    /**
     * Get top defaulters (overdue invoices)
     */
    public function getDefaulters(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $limit = $request->input('limit', 10);
        
        if (DB::getSchemaBuilder()->hasTable('fee_invoices')) {
            $defaulters = FeeInvoice::where('school_id', $schoolId)
                ->where('status', 'unpaid')
                ->where('due_date', '<', now())
                ->with(['student:id,first_name,last_name,middle_name', 'student.class:id,name'])
                ->orderBy('due_date', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($invoice) {
                    $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now());
                    return [
                        'id' => $invoice->id,
                        'student_id' => $invoice->student_id,
                        'student_name' => trim(($invoice->student->first_name ?? '') . ' ' . ($invoice->student->middle_name ?? '') . ' ' . ($invoice->student->last_name ?? '')),
                        'className' => $invoice->student->class->name ?? 'N/A',
                        'amount' => $invoice->total_amount,
                        'daysOverdue' => $daysOverdue,
                        'due_date' => $invoice->due_date
                    ];
                });
        } else {
            $defaulters = FeeSummary::where('school_id', $schoolId)
                ->where('payment_status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->with(['student:id,first_name,last_name,middle_name', 'student.class:id,name'])
                ->orderBy('due_date', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($summary) {
                    $daysOverdue = $summary->due_date ? Carbon::parse($summary->due_date)->diffInDays(now()) : 0;
                    return [
                        'id' => $summary->id,
                        'student_id' => $summary->student_id,
                        'student_name' => trim(($summary->student->first_name ?? '') . ' ' . ($summary->student->middle_name ?? '') . ' ' . ($summary->student->last_name ?? '')),
                        'className' => $summary->student->class->name ?? 'N/A',
                        'amount' => $summary->final_amount,
                        'daysOverdue' => $daysOverdue,
                        'due_date' => $summary->due_date
                    ];
                });
        }
        
        return response()->json([
            'success' => true,
            'data' => $defaulters
        ]);
    }
    
    /**
     * Get recent payments
     */
    public function getRecentPayments(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $limit = $request->input('limit', 10);
        
        // FeePayment might be linked via fee_summary_id, so we'll query differently
        $payments = FeePayment::whereHas('feeSummary', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with(['feeSummary.student:id,first_name,last_name,middle_name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                $student = $payment->feeSummary->student ?? null;
                return [
                    'id' => $payment->id,
                    'student_id' => $student->id ?? null,
                    'studentName' => $student 
                        ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                        : 'N/A',
                    'amount' => $payment->amount_paid ?? 0,
                    'time' => Carbon::parse($payment->created_at)->diffForHumans(),
                    'created_at' => $payment->created_at
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
    
    /**
     * Get recent disputes
     */
    public function getRecentDisputes(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $limit = $request->input('limit', 10);
        
        if (!DB::getSchemaBuilder()->hasTable('fee_disputes')) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
        $disputes = DB::table('fee_disputes')
            ->where('school_id', $schoolId)
            ->whereIn('status', ['raised', 'reviewing'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($dispute) {
                return [
                    'id' => $dispute->id,
                    'studentName' => $dispute->student_name ?? 'N/A',
                    'reason' => $dispute->reason ?? 'N/A',
                    'status' => $dispute->status ?? 'raised',
                    'time' => Carbon::parse($dispute->created_at)->diffForHumans()
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $disputes
        ]);
    }
    
    /**
     * Get insights (overdue trend, top classes with pending)
     */
    public function getInsights(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $month = $request->input('month', now()->format('Y-m'));
        
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        $prevMonth = Carbon::parse($month . '-01')->subMonth();
        
        // Current month overdue
        $currentOverdue = FeeInvoice::where('school_id', $schoolId)
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum('total_amount');
        
            // Previous month overdue
            if (DB::getSchemaBuilder()->hasTable('fee_invoices')) {
                $prevOverdue = FeeInvoice::where('school_id', $schoolId)
                    ->where('status', 'unpaid')
                    ->where('due_date', '<', $prevMonth->copy()->endOfMonth())
                    ->whereBetween('due_date', [$prevMonth->startOfMonth(), $prevMonth->copy()->endOfMonth()])
                    ->sum('total_amount');
            } else {
                $prevOverdue = FeeSummary::where('school_id', $schoolId)
                    ->where('month_for', $prevMonth->format('Y-m-d'))
                    ->where('payment_status', '!=', 'paid')
                    ->where('due_date', '<', $prevMonth->copy()->endOfMonth())
                    ->sum('final_amount');
            }
        
        $overdueChange = $prevOverdue > 0 
            ? round((($currentOverdue - $prevOverdue) / $prevOverdue) * 100, 1)
            : 0;
        
        // Top classes with pending fees
        if (DB::getSchemaBuilder()->hasTable('fee_invoices')) {
            $topPendingClasses = FeeInvoice::where('school_id', $schoolId)
                ->where('status', 'unpaid')
                ->whereBetween('due_date', [$startDate, $endDate])
                ->join('students', 'fee_invoices.student_id', '=', 'students.id')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->select('classes.id', 'classes.name', DB::raw('SUM(fee_invoices.total_amount) as amount'))
                ->groupBy('classes.id', 'classes.name')
                ->orderBy('amount', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'amount' => $item->amount
                    ];
                });
        } else {
            $topPendingClasses = FeeSummary::where('school_id', $schoolId)
                ->where('payment_status', '!=', 'paid')
                ->where('month_for', $startDate->format('Y-m-d'))
                ->join('students', 'fee_summaries.student_id', '=', 'students.id')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->select('classes.id', 'classes.name', DB::raw('SUM(fee_summaries.final_amount) as amount'))
                ->groupBy('classes.id', 'classes.name')
                ->orderBy('amount', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'amount' => $item->amount
                    ];
                });
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'overdue_change' => $overdueChange,
                'top_pending_classes' => $topPendingClasses
            ]
        ]);
    }
    
    /**
     * Preview monthly fees (NO DB write)
     */
    public function previewMonthlyFees(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $schoolId = $request->user()->school_id;
        $month = $request->input('month');
        
        // Get active students
        $students = Student::where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->with(['class.feeDefault'])
            ->get();
        
        $preview = $students->map(function ($student) use ($month) {
            $feeDefault = $student->class->feeDefault ?? null;
            $monthlyFee = $feeDefault->monthly_fee ?? 0;
            
            return [
                'student_id' => $student->id,
                'student_name' => trim($student->first_name . ' ' . ($student->last_name ?? '')),
                'class_name' => $student->class->name ?? 'N/A',
                'expected_amount' => $monthlyFee
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'total_students' => $preview->count(),
                'total_amount' => $preview->sum('expected_amount'),
                'students' => $preview
            ]
        ]);
    }
    
    /**
     * Generate monthly fees (dispatch job)
     */
    public function generateMonthlyFees(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
            'confirm' => 'required|boolean|accepted'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $schoolId = $request->user()->school_id;
        $month = $request->input('month');
        
        // Dispatch job (if exists)
        if (class_exists(\App\Jobs\GenerateMonthlyFeesJob::class)) {
            $batch = \Illuminate\Support\Facades\Bus::batch([
                new \App\Jobs\GenerateMonthlyFeesJob($schoolId, $month)
            ])->dispatch();
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly fee generation started',
                'job_batch_id' => $batch->id
            ], 202);
        }
        
        // Fallback: direct generation (for now)
        return response()->json([
            'success' => false,
            'message' => 'Fee generation job not implemented yet'
        ], 501);
    }
    
    /**
     * Send reminders (dispatch job)
     */
    public function sendReminders(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $month = $request->input('month', now()->format('Y-m'));
        
        // Dispatch job
        if (class_exists(\App\Jobs\SendFeeRemindersJob::class)) {
            $batch = \Illuminate\Support\Facades\Bus::batch([
                new \App\Jobs\SendFeeRemindersJob($schoolId, $month)
            ])->dispatch();
            
            return response()->json([
                'success' => true,
                'message' => 'Reminders sending started',
                'job_batch_id' => $batch->id
            ], 202);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Reminder job not implemented yet'
        ], 501);
    }
}
