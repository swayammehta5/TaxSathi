<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFirmRequest;
use App\Http\Resources\FirmResource;
use App\Models\Firm;
use Illuminate\Support\Facades\Log;

class FirmController extends Controller
{
    public function index()
    {
        $firms = Firm::query()->orderBy('name')->get();

        return response()->json(
            collect($firms)->map(fn (Firm $firm) => (new FirmResource($firm))->resolve())->values()->all()
        );
    }

    public function store(StoreFirmRequest $request)
    {
        $validated = $request->validated();

        if (! empty($validated['contact_number']) && empty($validated['phone'])) {
            $validated['phone'] = $validated['contact_number'];
        }

        Log::info('Creating firm', ['name' => $validated['name']]);

        $firm = Firm::query()->create($validated);

        return response()->json([
            'firm' => (new FirmResource($firm))->resolve(),
        ], 201);
    }
}
