<?php

namespace App\Rules;

use App\Models\Instrument;
use App\Support\AnswerValueCaster;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

/**
 * Validation rule that ensures each answer matches the selected question's response type.
 */
class AnswerMatchesQuestionType implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    /** @var Collection<int, object>|null */
    protected ?Collection $questions = null;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * The rule infers the answer index from the attribute path, then looks up the matching question definition for type validation.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! preg_match('/^answers\.(\d+)\.answer$/', $attribute, $matches)) {
            return;
        }

        $index = (int) $matches[1];
        $questionId = data_get($this->data, "answers.{$index}.question_id");

        if (! is_numeric($questionId)) {
            return;
        }

        $question = $this->questions()?->get((int) $questionId);

        if (! $question) {
            return;
        }

        if (! AnswerValueCaster::isValid($question->response_type, $value)) {
            $fail(sprintf(
                'Answer for question %d must match response type %s.',
                $question->id,
                $question->response_type
            ));
        }
    }

    /** @return Collection<int, object>|null */
    /**
     * Question metadata is cached per request so each answer validation does not re-query the database.
     */
    protected function questions(): ?Collection
    {
        if ($this->questions !== null) {
            return $this->questions;
        }

        $instrumentId = $this->data['instrument_id'] ?? null;

        if (! is_numeric($instrumentId)) {
            return null;
        }

        $instrument = Instrument::query()
            ->with('questions:id,instrument_id,response_type')
            ->find((int) $instrumentId);

        if (! $instrument) {
            return null;
        }

        return $this->questions = $instrument->questions->keyBy('id');
    }
}
