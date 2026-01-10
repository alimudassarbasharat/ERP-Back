<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FeeInvoice;
use App\Models\Challan;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateChallansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $schoolId = null,
        public ?int $invoiceId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = FeeInvoice::whereDoesntHave('challans')
            ->where('status', '!=', 'cancelled');

        if ($this->schoolId) {
            $query->where('school_id', $this->schoolId);
        }

        if ($this->invoiceId) {
            $query->where('id', $this->invoiceId);
        }

        $invoices = $query->get();

        foreach ($invoices as $invoice) {
            $this->generateChallanForInvoice($invoice);
        }
    }

    protected function generateChallanForInvoice(FeeInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $school = School::find($invoice->school_id);
            $student = $invoice->student;
            $class = $student->currentClass;

            // Generate unique challan number
            $challanNo = $this->generateChallanNumber($school, $invoice);

            // Create student and class snapshots
            $studentSnapshot = [
                'id' => $student->id,
                'name' => $student->name ?? ($student->first_name . ' ' . $student->last_name),
                'admission_no' => $student->admission_no ?? $student->admission_number ?? null,
                'father_name' => $student->father_name ?? null,
                'phone' => $student->phone ?? $student->phone_number ?? null,
            ];

            $classSnapshot = [
                'id' => $class->id ?? null,
                'name' => $class->name ?? null,
            ];

            // Create challan (PDF will be generated async via job)
            $challan = Challan::create([
                'school_id' => $invoice->school_id,
                'fee_invoice_id' => $invoice->id,
                'challan_no' => $challanNo,
                'amount' => $invoice->total_amount,
                'due_date' => $invoice->due_date,
                'status' => 'unpaid',
                'pdf_path' => null, // Will be set by GenerateChallanPdfJob
                'pdf_status' => 'pending',
                'student_snapshot' => $studentSnapshot,
                'class_snapshot' => $classSnapshot,
                'generated_by' => auth()->id(),
                'generated_at' => now(),
            ]);

            // Dispatch PDF generation job (async)
            \App\Jobs\GenerateChallanPdfJob::dispatch($challan->id);

            // Update invoice status if needed
            // Note: Invoice status remains 'unpaid' until payment is received
        });
    }

    protected function generateChallanNumber(School $school, FeeInvoice $invoice): string
    {
        $schoolCode = $school->code ?? 'SCH';
        $year = date('Y');
        $month = date('m', strtotime($invoice->billing_month));
        
        // Get last challan number for this school this month
        $lastChallan = Challan::where('school_id', $school->id)
            ->where('challan_no', 'like', "{$schoolCode}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastChallan) {
            $lastNumber = (int) substr($lastChallan->challan_no, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$schoolCode}-{$year}{$month}-{$newNumber}";
    }

}
