<?php

namespace App\Http\Requests;

use App\Models\Instrument;
use App\Support\AnswerValueCaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.answer' => ['present'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $instrument = Instrument::query()
                ->with('questions')
                ->find($this->integer('instrument_id'));

            if (! $instrument) {
                return;
            }

            $questions = $instrument->questions->keyBy('id');
            $submittedAnswers = collect($this->input('answers', []));
            $submittedQuestionIds = $submittedAnswers->pluck('question_id')->map(fn ($id) => (int) $id)->all();
            $expectedQuestionIds = $questions->keys()->map(fn ($id) => (int) $id)->all();

            sort($submittedQuestionIds);
            sort($expectedQuestionIds);

            if ($submittedQuestionIds !== $expectedQuestionIds) {
                $validator->errors()->add(
                    'answers',
                    'All questions for the selected instrument must be answered exactly once.'
                );

                return;
            }

            foreach ($submittedAnswers as $index => $payload) {
                $question = $questions->get((int) ($payload['question_id'] ?? 0));

                if (! $question) {
                    $validator->errors()->add("answers.$index.question_id", 'Question does not belong to the selected instrument.');
                    continue;
                }

                $answer = $payload['answer'] ?? null;

                if (! AnswerValueCaster::isValid($question->response_type, $answer)) {
                    $validator->errors()->add(
                        "answers.$index.answer",
                        sprintf('Answer for question %d must match response type %s.', $question->id, $question->response_type)
                    );
                }
            }
        });
    }
}
