<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Patient;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $submissions = $patient->submissions()
            ->with('instrument')
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return SubmissionResource::collection($submissions)->additional([
            'message' => 'Submissions retrieved successfully.',
        ]);
    }

    public function store(
        StoreSubmissionRequest $request,
        Patient $patient,
        SubmissionService $submissionService,
    ): JsonResponse {
        $submission = $submissionService->create($patient, $request->validated());

        return response()->json([
            'message' => 'Submission created successfully.',
            'data' => new SubmissionResource($submission),
        ], 201);
    }

    public function show(Patient $patient, int $submission): JsonResponse
    {
        $submission = Submission::query()
            ->where('patient_id', $patient->id)
            ->with([
                'instrument',
                'answers.question',
            ])
            ->find($submission);

        if (! $submission) {
            return response()->json([
                'message' => 'Resource not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Submission retrieved successfully.',
            'data' => new SubmissionResource($submission),
        ]);
    }
}
