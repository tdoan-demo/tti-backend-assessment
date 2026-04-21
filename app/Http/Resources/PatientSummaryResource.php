<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'patient_id' => $this['patient_id'],
            'instrument' => $this['instrument'],
            'total_submissions' => $this['total_submissions'],
            'date_range' => $this['date_range'],
            'questions' => $this['questions'],
        ];
    }
}
