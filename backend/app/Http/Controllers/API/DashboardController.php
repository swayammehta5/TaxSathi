<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function summary()
    {
        $payload = Cache::remember('api.dashboard.summary', now()->addSeconds(45), function () {
            $now = now();
            $month = (int) $now->month;
            $year = (int) $now->year;
            $daysInMonth = (int) $now->daysInMonth;

            $employeeCount = User::query()->count();
            $totalInvoices = Invoice::query()->count();

            $revenueQuery = Invoice::query()->where(function ($q) {
                $q->where('direction', 'outbound')->orWhereNull('direction');
            });

            $totalRevenue = round((float) $revenueQuery->sum('total_amount'), 2);

            $monthAttendance = Attendance::query()
                ->forMonth($month, $year)
                ->get();

            $presentDays = $monthAttendance->where('status', 'present')->count()
                + ($monthAttendance->where('status', 'half-day')->count() * 0.5);

            $totalWorkingSlots = max(1, $employeeCount * $daysInMonth);
            $attendancePercentage = round(($presentDays / $totalWorkingSlots) * 100, 1);

            $recent = Invoice::query()
                ->with('firm')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            return [
                'total_invoices' => $totalInvoices,
                'total_employees' => $employeeCount,
                'attendance_percentage_this_month' => min(100, $attendancePercentage),
                'total_revenue' => $totalRevenue,
                'recent_invoices' => InvoiceResource::collection($recent)->resolve(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => '',
        ]);
    }
}
