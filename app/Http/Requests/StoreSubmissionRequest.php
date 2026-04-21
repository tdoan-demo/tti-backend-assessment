<?php

namespace App\Http\Requests;

use App\Rules\AnswerMatchesQuestionType;
use App\Rules\InstrumentQuestionBelongsToInstrument;
use App\Rules\SubmissionQuestionsMatchInstrument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'integer', Rule::exists('instruments', 'id')],
            'submitted_at' => ['required', 'date'],
            'answers' => ['required', 'array', 'min:1', new SubmissionQuestionsMatchInstrument()],
            'answers.*.question_id' => ['required', 'integer', 'distinct', new InstrumentQuestionBelongsToInstrument()],
            'answers.*.answer' => ['present', new AnswerMatchesQuestionType()],
        ];
    }
}
