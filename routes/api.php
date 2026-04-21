<?php

use App\Http\Controllers\Api\InstrumentController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientSummaryController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

// Top-level creation endpoints.
Route::middleware('throttle:pro-write')->post('/patients', [PatientController::class, 'store']);
Route::middleware('throttle:pro-write')->post('/instruments', [InstrumentController::class, 'store']);

// Nested patient routes keep submission lookups scoped to the requested patient.
Route::scopeBindings()->group(function (): void {
    Route::middleware('throttle:pro-write')->post('/patients/{patient}/submissions', [SubmissionController::class, 'store']);

    Route::middleware('throttle:pro-read')->group(function (): void {
        Route::get('/patients/{patient}/submissions', [SubmissionController::class, 'index']);
        Route::get('/patients/{patient}/submissions/{submission}', [SubmissionController::class, 'show'])
            ->missing(fn () => response()->json(['message' => 'Resource not found.'], 404));
        Route::get('/patients/{patient}/summary', [PatientSummaryController::class, 'show']);
    });
});
