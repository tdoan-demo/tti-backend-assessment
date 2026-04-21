<?php

namespace App\Rules;

use App\Models\Instrument;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule that ensures a submitted question belongs to the selected instrument.
 */
class InstrumentQuestionBelongsToInstrument implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    /** @var array<int>|null */
    protected ?array $expectedQuestionIds = null;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * This prevents cross-instrument question IDs from being submitted against the wrong instrument.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $expectedQuestionIds = $this->expectedQuestionIds();

        if ($expectedQuestionIds === null || ! is_numeric($value)) {
            return;
        }

        if (! in_array((int) $value, $expectedQuestionIds, true)) {
            $fail('Question does not belong to the selected instrument.');
        }
    }

    /** @return array<int>|null */
    /**
     * The valid question IDs are cached per request because the same instrument is checked across multiple answer rows.
     */
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
            ->values()
            ->all();
    }
}
