<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ApplyLateFeeCommand extends Command
{ 
    protected $signature = 'fee:apply-late';
    protected $description = 'Apply late fee to unpaid or partially paid fee summaries past their due date';

    public function handle()
    {
        $today = Carbon::today();
        $late_fee_amount = 200; // Fixed late fee, can be dynamic

        $feeSummaries = DB::table('fee_summaries')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->where('due_date', '<', $today->toDateString())
            ->get();

        if ($feeSummaries->isEmpty()) {
            $this->info('No overdue fee summaries found.');
            return;
        }

        foreach ($feeSummaries as $summary) {
            // Avoid double-charging late fee
            if ($summary->late_fee >= $late_fee_amount) {
                $this->line("Late fee already applied to summary ID {$summary->id}.");
                continue;
            }

            $newLateFee = $late_fee_amount;
            $newFinalAmount = $summary->final_amount + $newLateFee;

            DB::table('fee_summaries')->where('id', $summary->id)->update([
                'late_fee' => $newLateFee,
                'final_amount' => $newFinalAmount,
                'updated_at' => now()
            ]);

            $this->info("Late fee applied to summary ID {$summary->id}");
        }

        $this->info("\n✔ Late fees successfully applied to overdue records.");
    }
} 