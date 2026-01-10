<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExamResult;
use App\Models\SchoolDocumentTemplateSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateMarksheetPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $resultId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $result = ExamResult::with(['student', 'exam', 'school', 'exam.examMarks.subject'])->find($this->resultId);

        if (!$result) {
            Log::error("Exam result {$this->resultId} not found");
            return;
        }

        try {
            // Get template setting for school (use marksheet template if exists, else default)
            $templateSetting = SchoolDocumentTemplateSetting::getDefault($result->school_id, 'marksheet');

            if (!$templateSetting) {
                // Fallback to voucher template or create default
                $templateSetting = SchoolDocumentTemplateSetting::getDefault($result->school_id, 'voucher');
            }

            if (!$templateSetting) {
                throw new \Exception("No marksheet template found for school {$result->school_id}");
            }

            $template = $templateSetting->template;
            $config = $templateSetting->config_json ?? [];

            // Load Blade view
            $viewPath = $template->blade_view;
            if (!view()->exists($viewPath)) {
                // Fallback to default marksheet view
                $viewPath = 'pdf.marksheet.default';
            }

            // Prepare data for view
            $data = [
                'result' => $result,
                'student' => $result->student,
                'exam' => $result->exam,
                'school' => $result->school,
                'snapshot' => $result->result_snapshot_json ?? [],
                'config' => $config,
            ];

            // Generate PDF
            $pdf = Pdf::loadView($viewPath, $data);
            $pdf->setPaper('a4', 'portrait');

            // Save PDF
            $filename = "marksheets/{$result->school_id}/{$result->exam_id}/marksheet-{$result->student_id}.pdf";
            Storage::disk('public')->put($filename, $pdf->output());

            // Update result with PDF path
            $result->update([
                'marksheet_pdf_path' => $filename,
            ]);

            Log::info("Marksheet PDF generated successfully for result {$this->resultId}");
        } catch (\Exception $e) {
            Log::error("Error generating marksheet PDF: " . $e->getMessage());
            throw $e;
        }
    }
}
