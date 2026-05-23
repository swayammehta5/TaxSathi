<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    /**
     * @deprecated Use GET /api/dashboard/summary instead. Kept for backward compatibility.
     */
    public function dashboard()
    {
        $today = now()->toDateString();
        $month = now()->month;
        $year = now()->year;

        $payload = Cache::remember('api.reports.dashboard', now()->addSeconds(45), function () use ($today, $month, $year) {
            return [
                'total_invoices' => Invoice::query()->count(),
                'invoices_this_month' => Invoice::query()
                    ->forMonth($month, $year)
                    ->count(),
                'active_employees' => User::query()->count(),
                'attendance_this_month' => Attendance::query()
                    ->forMonth($month, $year)
                    ->count(),
                'present_today' => Attendance::query()
                    ->forDate($today)
                    ->where('status', 'present')
                    ->count(),
            ];
        });

        return response()->json($payload);
    }

    public function attendance(Request $request)
    {
        $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'user_id' => ['sometimes', 'string'],
        ]);

        $month = $request->integer('month');
        $year = $request->integer('year');

        $attendances = Attendance::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
            ])
            ->forMonth($month, $year)
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (string) $request->input('user_id')))
            ->orderByDesc('date')
            ->get();

        $employees = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->when($request->filled('user_id'), fn ($q) => $q->where('id', (string) $request->input('user_id')))
            ->get()
            ->map(function (User $user) use ($attendances) {
                $rows = $attendances->where('user_id', $user->id);

                return [
                    'employee_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'present' => $rows->where('status', 'present')->count(),
                    'absent' => $rows->where('status', 'absent')->count(),
                    'half_day' => $rows->where('status', 'half-day')->count(),
                    'overtime_hours' => round((float) $rows->sum('overtime_hours'), 2),
                ];
            })
            ->values();

        $totals = [
            'total_present' => $attendances->where('status', 'present')->count(),
            'total_absent' => $attendances->where('status', 'absent')->count(),
            'total_half_day' => $attendances->where('status', 'half-day')->count(),
            'total_overtime' => round((float) $attendances->sum('overtime_hours'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'employees' => $employees,
                'totals' => $totals,
                'records' => AttendanceResource::collection($attendances)->resolve(),
            ],
            'message' => '',
        ]);
    }

    public function attendanceYearly(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'user_id' => ['nullable', 'string'],
        ]);

        $year = $request->integer('year');
        $userId = $request->filled('user_id') ? (string) $request->input('user_id') : null;

        $query = Attendance::query()->forYear($year);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $rows = $query->get();

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthRows = $rows->filter(fn ($a) => (int) date('n', strtotime((string) $a->date)) === $m);
            $byMonth[$m] = [
                'present' => $monthRows->where('status', 'present')->count(),
                'absent' => $monthRows->where('status', 'absent')->count(),
                'half_day' => $monthRows->where('status', 'half-day')->count(),
                'overtime_hours' => round((float) $monthRows->sum('overtime_hours'), 2),
            ];
        }

        return response()->json([
            'year' => $year,
            'user_id' => $userId,
            'by_month' => $byMonth,
        ]);
    }

    public function invoices(Request $request)
    {
        $request->validate([
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'firm_id' => ['sometimes', 'string'],
            'firm' => ['sometimes', 'string'],
            'direction' => ['sometimes', 'in:outbound,inbound'],
        ]);

        $firmId = $request->filled('firm_id')
            ? (string) $request->input('firm_id')
            : ($request->filled('firm') ? (string) $request->input('firm') : null);

        $query = Invoice::query()->with('firm');

        if ($request->filled('month') && $request->filled('year')) {
            $query->forMonth($request->integer('month'), $request->integer('year'));
        }

        if ($firmId) {
            $query->where('firm_id', $firmId);
        }

        if ($request->filled('direction')) {
            $query->where('direction', (string) $request->input('direction'));
        }

        $all = $query->orderByDesc('date')->orderByDesc('id')->get();

        $totalTax = round(
            (float) $all->sum('cgst') + (float) $all->sum('sgst') + (float) $all->sum('igst'),
            2
        );

        $outbound = $all->where('direction', 'outbound');
        $inbound = $all->where('direction', 'inbound');

        $payload = [
            'total_count' => $all->count(),
            'total_amount' => round((float) $all->sum('total_amount'), 2),
            'total_revenue' => round((float) $all->sum('total_amount'), 2),
            'total_tax' => $totalTax,
            'total_cgst' => round((float) $all->sum('cgst'), 2),
            'total_sgst' => round((float) $all->sum('sgst'), 2),
            'total_igst' => round((float) $all->sum('igst'), 2),
            'outbound' => [
                'count' => $outbound->count(),
                'total_amount' => round((float) $outbound->sum('total_amount'), 2),
            ],
            'inbound' => [
                'count' => $inbound->count(),
                'total_amount' => round((float) $inbound->sum('total_amount'), 2),
            ],
            'invoices' => InvoiceResource::collection($all)->resolve(),
            'records' => InvoiceResource::collection($all)->resolve(),
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => '',
        ]);
    }
}
