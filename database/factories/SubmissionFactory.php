<?php

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'instrument_id' => Instrument::factory(),
            'submitted_at' => now(),
        ];
    }
}
