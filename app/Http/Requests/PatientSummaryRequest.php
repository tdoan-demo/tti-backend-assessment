<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request that validates summary query parameters.
 */
class PatientSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Summary requests accept `instrument_id` via the query string, so validation is limited to query parameters.
     */
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