<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishExamResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $examId,
        public int $publishedBy
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exam = Exam::find($this->examId);

        if (!$exam) {
            Log::error("Exam {$this->examId} not found");
            return;
        }

        if (!$exam->isReadyToPublish()) {
            Log::warning("Exam {$this->examId} is not ready to publish");
            return;
        }

        DB::transaction(function () use ($exam) {
            // Lock all papers and marks
            $exam->examPapers()->where('status', '!=', \App\Enums\ExamPaperStatus::LOCKED)->each(function ($paper) {
                $paper->lock();
            });

            $exam->examMarks()->where('status', '!=', \App\Enums\ExamMarkStatus::LOCKED)->each(function ($mark) {
                $mark->lock();
            });

            // Publish all results
            $results = ExamResult::where('exam_id', $exam->id)
                ->where('status', 'provisional')
                ->get();

            foreach ($results as $result) {
                $result->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                // Trigger notification to parents
                $this->notifyResultPublished($result);
            }

            Log::info("Published results for exam {$this->examId}: " . $results->count() . " results published");
        });
    }

    protected function notifyResultPublished(ExamResult $result): void
    {
        NotificationEvent::create([
            'school_id' => $result->school_id,
            'type' => 'result_published',
            'reference_type' => 'exam_result',
            'reference_id' => $result->id,
            'trigger' => 'on_publish',
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);
    }
}
