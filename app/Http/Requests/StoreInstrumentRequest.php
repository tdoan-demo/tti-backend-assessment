<?php

namespace App\Http\Requests;

use App\Support\AnswerValueCaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request that validates instrument creation payloads.
 */
class StoreInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.response_type' => ['required', Rule::in(AnswerValueCaster::allowedTypes())],
            'questions.*.sort_order' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
