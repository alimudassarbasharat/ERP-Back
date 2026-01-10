<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class JobStatusController extends Controller
{
    /**
     * Get job batch status for progress tracking
     */
    public function getBatchStatus(Request $request, $batchId)
    {
        try {
            $batch = Bus::findBatch($batchId);
            
            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job batch not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'failed_jobs' => $batch->failedJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'progress' => $batch->totalJobs > 0 
                        ? round(($batch->processedJobs() / $batch->totalJobs) * 100, 1)
                        : 0,
                    'finished' => $batch->finished(),
                    'cancelled' => $batch->cancelled(),
                    'created_at' => $batch->createdAt,
                    'finished_at' => $batch->finishedAt,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get job status: ' . $e->getMessage()
            ], 500);
        }
    }
}
