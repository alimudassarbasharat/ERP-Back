<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FeeInvoice;
use App\Models\SchoolDocumentTemplateSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $invoiceId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = FeeInvoice::with(['student', 'school', 'session', 'items'])->find($this->invoiceId);

        if (!$invoice) {
            Log::error("Invoice {$this->invoiceId} not found");
            return;
        }

        // Update status to generating
        $invoice->update(['pdf_status' => 'generating']);

        try {
            // Get template setting for school
            $templateSetting = SchoolDocumentTemplateSetting::getDefault($invoice->school_id, 'invoice');

            if (!$templateSetting) {
                throw new \Exception("No default invoice template found for school {$invoice->school_id}");
            }

            $template = $templateSetting->template;
            $config = $templateSetting->config_json ?? [];

            // Load Blade view
            $viewPath = $template->blade_view;
            if (!view()->exists($viewPath)) {
                throw new \Exception("Template view not found: {$viewPath}");
            }

            // Prepare data for view
            $data = [
                'invoice' => $invoice,
                'student' => $invoice->student,
                'school' => $invoice->school,
                'session' => $invoice->session,
                'items' => $invoice->items,
                'config' => $config,
            ];

            // Generate PDF
            $pdf = Pdf::loadView($viewPath, $data);
            $pdf->setPaper('a4', 'portrait');

            // Save PDF
            $filename = "invoices/{$invoice->school_id}/{$invoice->id}/invoice-{$invoice->id}.pdf";
            Storage::disk('public')->put($filename, $pdf->output());

            // Update invoice with PDF path and status
            $invoice->update([
                'pdf_path' => $filename,
                'pdf_status' => 'completed',
                'pdf_generated_at' => now(),
            ]);

            Log::info("Invoice PDF generated successfully for invoice {$this->invoiceId}");
        } catch (\Exception $e) {
            Log::error("Error generating invoice PDF: " . $e->getMessage());
            $invoice->update([
                'pdf_status' => 'failed',
            ]);
            throw $e;
        }
    }
}
