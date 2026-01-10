<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Exam;
use App\Services\ExamResultsService;
use Illuminate\Support\Facades\Log;

class GenerateExamResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $examId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ExamResultsService $service): void
    {
        $exam = Exam::with(['examClasses', 'examMarks'])->find($this->examId);

        if (!$exam) {
            Log::error("Exam {$this->examId} not found");
            return;
        }

        if (!$service->isReadyForResults($exam)) {
            Log::warning("Exam {$this->examId} is not ready for results generation");
            return;
        }

        try {
            $results = $service->generateResults($exam);
            
            Log::info("Generated results for exam {$this->examId}: " . count($results) . " results created");

            // Dispatch marksheet PDF generation for all results
            foreach ($results as $result) {
                GenerateMarksheetPdfJob::dispatch($result->id);
            }
        } catch (\Exception $e) {
            Log::error("Error generating exam results: " . $e->getMessage());
            throw $e;
        }
    }
}
