<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index()
    {
        $paginator = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->paginate(100);

        return response()->json([
            'data' => collect($paginator->items())->map(
                fn (User $user) => (new UserResource($user))->resolve()
            )->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'password' => null,
        ]);

        return response()->json([
            'employee' => (new UserResource($user))->resolve(),
        ], 201);
    }

    public function show(string $employee)
    {
        $user = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])
            ->findOrFail($employee);

        return response()->json((new UserResource($user))->resolve());
    }

    public function update(UpdateEmployeeRequest $request, string $employee)
    {
        $user = User::query()->findOrFail($employee);

        $user->fill($request->validated());
        $user->save();

        return response()->json((new UserResource($user))->resolve());
    }

    public function destroy(Request $request, string $employee)
    {
        Log::info('Deleting employee ID: '.$employee);

        $user = User::query()->findOrFail($employee);

        if ((string) $user->getKey() === (string) $request->user()->getKey()) {
            return response()->json(['message' => 'You cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Employee deleted successfully',
        ], 200);
    }
}
