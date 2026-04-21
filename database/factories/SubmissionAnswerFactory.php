<?php

namespace Database\Factories;

use App\Models\InstrumentQuestion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAnswer>
 */
/**
 * Factory for creating submission answer records in tests and seed data.
 */
class SubmissionAnswerFactory extends Factory
{
    protected $model = SubmissionAnswer::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'instrument_question_id' => InstrumentQuestion::factory(),
            'answer_value' => '',
        ];
    }
}
