<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstrumentRequest;
use App\Http\Resources\InstrumentResource;
use App\Models\Instrument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstrumentController extends Controller
{
    public function store(StoreInstrumentRequest $request): JsonResponse
    {
        $instrument = DB::transaction(function () use ($request): Instrument {
            $instrument = Instrument::query()->create([
                'title' => $request->validated('title'),
                'description' => $request->validated('description'),
            ]);

            $instrument->questions()->createMany($request->validated('questions'));

            return $instrument->load('questions');
        });

        return response()->json([
            'message' => 'Instrument created successfully.',
            'data' => new InstrumentResource($instrument),
        ], 201);
    }
}
