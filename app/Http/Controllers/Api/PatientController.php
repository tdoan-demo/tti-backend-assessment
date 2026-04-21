<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::query()->create($request->validated());

        return response()->json([
            'message' => 'Patient created successfully.',
            'data' => new PatientResource($patient),
        ], 201);
    }
}
