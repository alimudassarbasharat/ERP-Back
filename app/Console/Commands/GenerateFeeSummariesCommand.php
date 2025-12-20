<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Student;

class GenerateFeeSummariesCommand extends Command
{
    protected $signature = 'fee:generate {--month=}';
    protected $description = 'Generate monthly fee summaries for all active students';

    public function handle()
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $students = Student::with(['class', 'section'])->get();

        foreach ($students as $student) {
            $base_fee = DB::table('fee_defaults')
                ->where('class_id', $student->class_id)
                ->where('effective_from', '<=', $month)
                ->orderByDesc('effective_from')
                ->value('monthly_fee');

            if (!$base_fee) {
                $this->warn("No base fee found for student ID {$student->id} in class {$student->class_id}.");
                continue;
            }

            $last_month = $month->copy()->subMonth();
            $last_summary = DB::table('fee_summaries')
                ->where('student_id', $student->id)
                ->where('month_for', $last_month)
                ->first();

            $carry_forward = 0;
            if ($last_summary) {
                $total_paid = DB::table('fee_payments')
                    ->where('fee_summary_id', $last_summary->id)
                    ->sum('amount_paid');

                $carry_forward = max($last_summary->final_amount - $total_paid, 0);
            }

            $discount = 0; // You can add dynamic logic here
            $late_fee = 0;

            DB::table('fee_summaries')->insert([
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'section_id' => $student->section_id,
                'month_for' => $month->toDateString(),
                'base_fee' => $base_fee,
                'discount_amount' => $discount,
                'carry_forward' => $carry_forward,
                'late_fee' => $late_fee,
                'final_amount' => $base_fee - $discount + $carry_forward + $late_fee,
                'due_date' => $month->copy()->addDays(10)->toDateString(),
                'payment_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("Fee summary generated for student ID {$student->id} for {$month->format('F Y')}");
        }

        $this->info("\n✔ All fee summaries generated successfully for {$month->format('F Y')}.");
    }
} 