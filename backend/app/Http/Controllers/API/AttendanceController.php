<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkMarkAttendanceRequest;
use App\Http\Requests\GetAttendanceByDateRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\UserResource;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceController extends Controller
{
    /**
     * @param  mixed  $data
     */
    protected function successResponse($data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public function bulkStore(BulkMarkAttendanceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $date = $validated['date'];

        try {
            $ids = [];
            foreach ($validated['records'] as $row) {
                $userId = (string) ($row['employee_id'] ?? $row['user_id']);

                $attendance = Attendance::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'date' => $date,
                    ],
                    [
                        'status' => $row['status'],
                        'overtime_hours' => $row['overtime_hours'] ?? 0,
                    ]
                );

                $ids[] = $attendance->id;
            }

            $records = Attendance::query()
                ->with([
                    'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
                ])
                ->whereIn('id', $ids)
                ->orderBy('user_id')
                ->get();

            return $this->successResponse([
                'date' => $date,
                'records' => AttendanceResource::collection($records)->resolve(),
            ], 'Attendance saved successfully');
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to save attendance.',
            ], 500);
        }
    }

    public function byDate(GetAttendanceByDateRequest $request): JsonResponse
    {
        $date = $request->validated('date');

        $records = Attendance::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
            ])
            ->forDate($date)
            ->orderBy('user_id')
            ->get();

        return $this->successResponse([
            'date' => $date,
            'records' => AttendanceResource::collection($records)->resolve(),
        ]);
    }

    public function history(string $employeeId): JsonResponse
    {
        $employee = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])
            ->findOrFail($employeeId);

        $records = Attendance::query()
            ->where('user_id', $employee->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return $this->successResponse([
            'employee' => (new UserResource($employee))->resolve(),
            'summary' => [
                'total_present' => $records->where('status', 'present')->count(),
                'total_absent' => $records->where('status', 'absent')->count(),
                'total_half_days' => $records->where('status', 'half-day')->count(),
                'total_overtime' => round((float) $records->sum('overtime_hours'), 2),
            ],
            'records' => AttendanceResource::collection($records)->resolve(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Attendance::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date')) {
            $query->forDate((string) $request->input('date'));
        }

        return AttendanceResource::collection($query->paginate(15));
    }

    public function store(StoreAttendanceRequest $request)
    {
        $validated = $request->validated();

        $attendance = Attendance::query()->updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'overtime_hours' => $validated['overtime_hours'] ?? 0,
            ]
        );

        $attendance->load([
            'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
        ]);

        return response()->json([
            'message' => 'Attendance marked',
            'attendance' => (new AttendanceResource($attendance))->resolve(),
        ]);
    }

    public function show(string $id)
    {
        $attendance = Attendance::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
            ])
            ->findOrFail($id);

        return response()->json((new AttendanceResource($attendance))->resolve());
    }

    public function update(UpdateAttendanceRequest $request, string $id)
    {
        $validated = $request->validated();

        $attendance = Attendance::query()->findOrFail($id);
        $attendance->update([
            'user_id' => $validated['user_id'],
            'date' => $validated['date'],
            'status' => $validated['status'],
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
        ]);

        $attendance->load([
            'user' => static fn ($q) => $q->select('id', 'name', 'email', 'role'),
        ]);

        return response()->json([
            'message' => 'Attendance updated',
            'attendance' => (new AttendanceResource($attendance))->resolve(),
        ]);
    }

    public function destroy(string $id)
    {
        $attendance = Attendance::query()->findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Attendance deleted']);
    }
}
