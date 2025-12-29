<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get monthly income data for dashboard charts
     */
    public function getMonthlyIncome(Request $request)
    {
        try {
            $period = $request->input('period', 6);
            $data = $this->adminService->getMonthlyIncome($period);

            return response()->json([
                'status' => true,
                'message' => 'Monthly income data fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch monthly income data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get fee distribution data for dashboard charts
     */
    public function getFeeDistribution(Request $request)
    {
        try {
            $period = $request->input('period', 'Monthly');
            $data = $this->adminService->getFeeDistribution($period);

            return response()->json([
                'status' => true,
                'message' => 'Fee distribution data fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch fee distribution data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics()
    {
        try {
            $currentMonth = now();
            
            $totalFee = DB::table('fee_summaries')
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->sum('final_amount');

            $receivedFee = DB::table('fee_payments')
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->where('status', 'paid')
                ->sum('amount');

            $presentStudents = DB::table('attendance')
                ->whereDate('date', today())
                ->where('status', 'present')
                ->count();

            $absentStudents = DB::table('attendance')
                ->whereDate('date', today())
                ->where('status', 'absent')
                ->count();

            return response()->json([
                'status' => true,
                'message' => 'Dashboard statistics fetched successfully',
                'data' => [
                    'totalFee' => $totalFee ?? 0,
                    'receivedFee' => $receivedFee ?? 0,
                    'presentStudents' => $presentStudents ?? 0,
                    'absentStudents' => $absentStudents ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'data' => [
                    'totalFee' => 0,
                    'receivedFee' => 0,
                    'presentStudents' => 0,
                    'absentStudents' => 0
                ]
            ], 500);
        }
    }
}

