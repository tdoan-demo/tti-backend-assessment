<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientSummaryRequest;
use App\Http\Resources\PatientSummaryResource;
use App\Models\Instrument;
use App\Models\Patient;
use App\Services\PatientSummaryService;
use Illuminate\Http\JsonResponse;

class PatientSummaryController extends Controller
{
    public function show(
        PatientSummaryRequest $request,
        Patient $patient,
        PatientSummaryService $patientSummaryService,
    ): JsonResponse {
        $instrument = Instrument::query()->findOrFail((int) $request->validated('instrument_id'));

        $summary = $patientSummaryService->build($patient, $instrument);

        return response()->json([
            'message' => 'Patient summary retrieved successfully.',
            'data' => new PatientSummaryResource($summary),
        ]);
    }
}
