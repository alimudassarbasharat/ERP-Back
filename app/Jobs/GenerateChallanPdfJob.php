<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Challan;
use App\Models\SchoolDocumentTemplateSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateChallanPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $challanId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $challan = Challan::with(['feeInvoice.student', 'feeInvoice.items', 'school', 'feeInvoice.session'])->find($this->challanId);

        if (!$challan) {
            Log::error("Challan {$this->challanId} not found");
            return;
        }

        // Update status to generating
        $challan->update(['pdf_status' => 'generating']);

        try {
            // Get template setting for school
            $templateSetting = SchoolDocumentTemplateSetting::getDefault($challan->school_id, 'voucher');

            if (!$templateSetting) {
                throw new \Exception("No default voucher template found for school {$challan->school_id}");
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
                'challan' => $challan,
                'invoice' => $challan->feeInvoice,
                'student' => $challan->feeInvoice->student,
                'school' => $challan->school,
                'session' => $challan->feeInvoice->session,
                'items' => $challan->feeInvoice->items,
                'config' => $config,
            ];

            // Generate PDF
            $pdf = Pdf::loadView($viewPath, $data);
            $pdf->setPaper('a4', 'portrait');

            // Save PDF
            $filename = "challans/{$challan->school_id}/{$challan->id}/challan-{$challan->challan_no}.pdf";
            Storage::disk('public')->put($filename, $pdf->output());

            // Update challan with PDF path and status
            $challan->update([
                'pdf_path' => $filename,
                'pdf_status' => 'completed',
                'pdf_generated_at' => now(),
            ]);

            Log::info("Challan PDF generated successfully for challan {$this->challanId}");
        } catch (\Exception $e) {
            Log::error("Error generating challan PDF: " . $e->getMessage());
            $challan->update([
                'pdf_status' => 'failed',
            ]);
            throw $e;
        }
    }
}
