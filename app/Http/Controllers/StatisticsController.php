<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Get counts for classes, sections, and subjects
     */
    public function getCounts()
    {
        try {
            $counts = [
                'classes' => Classes::count(),
                'sections' => Section::count(),
                'subjects' => Subject::count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Counts fetched successfully',
                'result' => $counts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch counts',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 