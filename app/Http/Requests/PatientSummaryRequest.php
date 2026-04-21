<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        return $this->query();
    }

    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'integer', Rule::exists('instruments', 'id')],
        ];
    }
}