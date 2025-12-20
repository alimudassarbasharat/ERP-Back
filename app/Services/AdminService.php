<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function getAdmins($request)
    {
        $query = Admin::with(['user', 'department', 'parent'])
            ->when($request->search, function($q) use ($request) {
                return $q->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->department_id, function($q) use ($request) {
                return $q->where('department_id', $request->department_id);
            })
            ->when($request->level, function($q) use ($request) {
                return $q->where('level', $request->level);
            });

        return $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);
    }

    public function createAdmin($data)
    {
        DB::beginTransaction();
        try {
            $admin = Admin::create($data);
            $user = User::find($data['user_id']);
            $user->assignRole('admin_level_' . $data['level']);
            DB::commit();
            return $admin->load(['user', 'department', 'parent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateAdmin(Admin $admin, $data)
    {
        DB::beginTransaction();
        try {
            $admin->update($data);
            if (isset($data['level'])) {
                $user = $admin->user;
                $user->syncRoles(['admin_level_' . $data['level']]);
            }
            DB::commit();
            return $admin->load(['user', 'department', 'parent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteAdmin(Admin $admin)
    {
        DB::beginTransaction();
        try {
            $user = $admin->user;
            $user->removeRole('admin_level_' . $admin->level);
            $admin->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getSubordinates(Admin $admin)
    {
        return $admin->subordinates()
            ->with(['user', 'department'])
            ->get();
    }

    public function getMonthlyIncome($period = 6)
    {
        $currentMonth = now();
        $previousMonth = now()->subMonth();

        $currentMonthData = DB::table('fee_payments')
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('SUM(amount) as total'))
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->groupBy('week')
            ->orderBy('week')
            ->pluck('total', 'week')
            ->toArray();

        $previousMonthData = DB::table('fee_payments')
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('SUM(amount) as total'))
            ->whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->groupBy('week')
            ->orderBy('week')
            ->pluck('total', 'week')
            ->toArray();

        $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        $currentMonthValues = array_values($currentMonthData);
        $previousMonthValues = array_values($previousMonthData);

        $totalFee = DB::table('fee_payments')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('amount');

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

        return [
            'labels' => $labels,
            'currentMonth' => $currentMonthValues,
            'previousMonth' => $previousMonthValues,
            'totalFee' => $totalFee,
            'receivedFee' => $receivedFee,
            'presentStudents' => $presentStudents,
            'absentStudents' => $absentStudents
        ];
    }

    public function getFeeDistribution($period = 'Monthly')
    {
        $query = DB::table('fee_payments')
            ->select('fee_type', DB::raw('SUM(amount) as total'))
            ->where('status', 'paid');

        if ($period === 'Daily') {
            $query->whereDate('created_at', today());
        } else {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        $distribution = $query->groupBy('fee_type')
                            ->get();

        return [
            'labels' => $distribution->pluck('fee_type')->toArray(),
            'values' => $distribution->pluck('total')->toArray()
        ];
    }
} 