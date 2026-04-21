<?php

namespace App\Rules;

use App\Models\Instrument;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SubmissionQuestionsMatchInstrument implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    /** @var array<int>|null */
    protected ?array $expectedQuestionIds = null;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $expectedQuestionIds = $this->expectedQuestionIds();

        if ($expectedQuestionIds === null) {
            return;
        }

        $submittedQuestionIds = collect($value)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($submittedQuestionIds !== $expectedQuestionIds) {
            $fail('All questions for the selected instrument must be answered exactly once.');
        }
    }

    /** @return array<int>|null */
    protected function expectedQuestionIds(): ?array
    {
        if ($this->expectedQuestionIds !== null) {
            return $this->expectedQuestionIds;
        }

        $instrumentId = $this->data['instrument_id'] ?? null;

        if (! is_numeric($instrumentId)) {
            return null;
        }

        $instrument = Instrument::query()->with('questions:id,instrument_id')->find((int) $instrumentId);

        if (! $instrument) {
            return null;
        }

        return $this->expectedQuestionIds = $instrument->questions
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }
}
