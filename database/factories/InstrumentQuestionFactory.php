<?php

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\InstrumentQuestion;
use App\Support\AnswerValueCaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstrumentQuestion>
 */
/**
 * Factory for creating instrument questions with convenient response-type states.
 */
class InstrumentQuestionFactory extends Factory
{
    protected $model = InstrumentQuestion::class;

    public function definition(): array
    {
        return [
            'instrument_id' => Instrument::factory(),
            'prompt' => fake()->sentence() . '?',
            'response_type' => AnswerValueCaster::FREE_TEXT,
            'sort_order' => 1,
        ];
    }

    /**
     * Convenience state for scale-based questions used in tests and sample data.
     */
    public function scale(): static
    {
        return $this->state(fn () => [
            'response_type' => AnswerValueCaster::SCALE_1_5,
        ]);
    }

    /**
     * Convenience state for yes/no questions used in tests and sample data.
     */
    public function yesNo(): static
    {
        return $this->state(fn () => [
            'response_type' => AnswerValueCaster::YES_NO,
        ]);
    }

    /**
     * Convenience state for free-text questions used in tests and sample data.
     */
    public function freeText(): static
    {
        return $this->state(fn () => [
            'response_type' => AnswerValueCaster::FREE_TEXT,
        ]);
    }
}
