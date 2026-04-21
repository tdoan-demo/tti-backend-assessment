<?php

use App\Http\Controllers\Api\InstrumentController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientSummaryController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::post('/patients', [PatientController::class, 'store']);
Route::post('/instruments', [InstrumentController::class, 'store']);

Route::scopeBindings()->group(function (): void {
    Route::post('/patients/{patient}/submissions', [SubmissionController::class, 'store']);
    Route::get('/patients/{patient}/submissions', [SubmissionController::class, 'index']);
    Route::get('/patients/{patient}/submissions/{submission}', [SubmissionController::class, 'show'])
        ->missing(fn () => response()->json(['message' => 'Resource not found.'], 404));
    Route::get('/patients/{patient}/summary', [PatientSummaryController::class, 'show']);
});
