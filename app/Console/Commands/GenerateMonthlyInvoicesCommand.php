<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Session;
use App\Models\FeeStructure;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\StudentFeeDiscount;
use App\Models\SiblingDiscount;
use App\Models\FamilyGroup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:generate-monthly-invoices {--school_id=} {--month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly fee invoices for active students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schoolId = $this->option('school_id');
        $month = $this->option('month') ?: Carbon::now()->format('Y-m');
        $billingMonth = Carbon::parse($month . '-01');

        $this->info("Generating invoices for month: {$month}");

        // Get active session
        $sessionQuery = Session::where('is_active', true);
        if ($schoolId) {
            $sessionQuery->where('school_id', $schoolId);
        }
        $session = $sessionQuery->first();

        if (!$session) {
            $this->error('No active session found.');
            return 1;
        }

        // Get active students
        $studentsQuery = Student::where('status', 'active')
            ->where('current_session_id', $session->id)
            ->whereNotNull('current_class_id');
        
        if ($schoolId) {
            $studentsQuery->where('school_id', $schoolId);
        }

        $students = $studentsQuery->get();
        $this->info("Found {$students->count()} active students");

        $generated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Check if invoice already exists
                $existingInvoice = FeeInvoice::where('student_id', $student->id)
                    ->where('session_id', $session->id)
                    ->where('billing_month', $billingMonth->format('Y-m-d'))
                    ->first();

                if ($existingInvoice) {
                    $skipped++;
                    continue;
                }

                // Get fee plan for student
                $feePlan = \App\Models\StudentFeePlan::where('student_id', $student->id)
                    ->where('session_id', $session->id)
                    ->first();

                if (!$feePlan) {
                    $this->warn("No fee plan found for student {$student->id}, skipping...");
                    $skipped++;
                    continue;
                }

                // Get fee structures for student's class and session
                $feeStructures = FeeStructure::where('school_id', $student->school_id)
                    ->where('session_id', $session->id)
                    ->where('class_id', $feePlan->class_id)
                    ->get();

                if ($feeStructures->isEmpty()) {
                    $this->warn("No fee structure found for student {$student->id}, skipping...");
                    $skipped++;
                    continue;
                }

                // Calculate subtotal (only monthly fees for monthly invoices)
                $subtotal = 0;
                $invoiceItems = [];

                foreach ($feeStructures as $structure) {
                    if ($structure->feeHead->frequency === 'monthly') {
                        $subtotal += $structure->amount;
                        $invoiceItems[] = [
                            'fee_head_name' => $structure->feeHead->name,
                            'fee_head_id' => $structure->fee_head_id,
                            'amount' => $structure->amount,
                        ];
                    }
                }

                if (empty($invoiceItems)) {
                    $skipped++;
                    continue;
                }

                // Apply student-specific discounts
                $discountTotal = 0;
                $studentDiscounts = StudentFeeDiscount::where('student_id', $student->id)
                    ->where(function($query) use ($session) {
                        $query->where('session_id', $session->id)
                            ->orWhereNull('session_id');
                    })
                    ->get();

                foreach ($studentDiscounts as $discount) {
                    if ($discount->fee_head_id) {
                        // Apply to specific fee head
                        foreach ($invoiceItems as &$item) {
                            if ($item['fee_head_id'] == $discount->fee_head_id) {
                                $discountAmount = $discount->discount_type === 'percentage'
                                    ? ($item['amount'] * $discount->discount_value / 100)
                                    : $discount->discount_value;
                                $item['amount'] = max(0, $item['amount'] - $discountAmount);
                                $discountTotal += $discountAmount;
                            }
                        }
                    } else {
                        // Apply to all fees
                        $discountAmount = $discount->discount_type === 'percentage'
                            ? ($subtotal * $discount->discount_value / 100)
                            : $discount->discount_value;
                        $discountTotal += $discountAmount;
                    }
                }

                // Apply sibling discounts
                $familyGroups = $student->familyGroups;
                foreach ($familyGroups as $familyGroup) {
                    $siblings = $familyGroup->students()
                        ->where('status', 'active')
                        ->where('current_session_id', $session->id)
                        ->count();

                    if ($siblings >= 2) {
                        $siblingDiscounts = SiblingDiscount::where('school_id', $student->school_id)
                            ->where('session_id', $session->id)
                            ->where('min_siblings', '<=', $siblings)
                            ->orderBy('min_siblings', 'desc')
                            ->first();

                        if ($siblingDiscounts) {
                            if ($siblingDiscounts->apply_on === 'tuition') {
                                // Apply only to tuition fee
                                foreach ($invoiceItems as &$item) {
                                    if (stripos($item['fee_head_name'], 'tuition') !== false) {
                                        $discountAmount = $siblingDiscounts->discount_type === 'percentage'
                                            ? ($item['amount'] * $siblingDiscounts->discount_value / 100)
                                            : $siblingDiscounts->discount_value;
                                        $item['amount'] = max(0, $item['amount'] - $discountAmount);
                                        $discountTotal += $discountAmount;
                                    }
                                }
                            } elseif ($siblingDiscounts->apply_on === 'all') {
                                // Apply to all
                                $discountAmount = $siblingDiscounts->discount_type === 'percentage'
                                    ? ($subtotal * $siblingDiscounts->discount_value / 100)
                                    : $siblingDiscounts->discount_value;
                                $discountTotal += $discountAmount;
                            }
                        }
                    }
                }

                // Recalculate subtotal after discounts
                $subtotal = array_sum(array_column($invoiceItems, 'amount'));
                $totalAmount = $subtotal;

                // Create invoice
                $invoice = FeeInvoice::create([
                    'school_id' => $student->school_id,
                    'student_id' => $student->id,
                    'session_id' => $session->id,
                    'billing_month' => $billingMonth->format('Y-m-d'),
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'total_amount' => $totalAmount,
                    'status' => 'unpaid',
                    'due_date' => $billingMonth->copy()->addDays(10), // 10 days from billing month start
                    'generated_at' => now(),
                ]);

                // Create invoice items (snapshot)
                foreach ($invoiceItems as $item) {
                    FeeInvoiceItem::create([
                        'fee_invoice_id' => $invoice->id,
                        'fee_head_name' => $item['fee_head_name'],
                        'fee_head_id' => $item['fee_head_id'],
                        'amount' => $item['amount'],
                    ]);
                }

                $generated++;
            }

            DB::commit();
            $this->info("✅ Generated {$generated} invoices, skipped {$skipped}");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
