<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use App\Support\AnswerValueCaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $patient = Patient::query()->create([
                'name' => 'Ava Chen',
                'date_of_birth' => '1991-06-12',
                'mrn' => 'MRN-10001',
            ]);

            $instrument = Instrument::query()->create([
                'title' => 'Weekly Symptom Check-In',
                'description' => 'A short weekly symptom and quality of life assessment.',
            ]);

            $questions = $instrument->questions()->createMany([
                [
                    'prompt' => 'Rate your fatigue this week.',
                    'response_type' => AnswerValueCaster::SCALE_1_5,
                    'sort_order' => 1,
                ],
                [
                    'prompt' => 'Did you experience nausea?',
                    'response_type' => AnswerValueCaster::YES_NO,
                    'sort_order' => 2,
                ],
                [
                    'prompt' => 'Anything else you want your care team to know?',
                    'response_type' => AnswerValueCaster::FREE_TEXT,
                    'sort_order' => 3,
                ],
            ]);

            $firstSubmission = Submission::query()->create([
                'patient_id' => $patient->id,
                'instrument_id' => $instrument->id,
                'submitted_at' => Carbon::parse('2026-04-01T10:00:00Z'),
            ]);

            $firstSubmission->answers()->createMany([
                [
                    'instrument_question_id' => $questions[0]->id,
                    'answer_value' => '4',
                ],
                [
                    'instrument_question_id' => $questions[1]->id,
                    'answer_value' => '1',
                ],
                [
                    'instrument_question_id' => $questions[2]->id,
                    'answer_value' => 'Mild fatigue after treatment day.',
                ],
            ]);

            $secondSubmission = Submission::query()->create([
                'patient_id' => $patient->id,
                'instrument_id' => $instrument->id,
                'submitted_at' => Carbon::parse('2026-04-08T10:00:00Z'),
            ]);

            $secondSubmission->answers()->createMany([
                [
                    'instrument_question_id' => $questions[0]->id,
                    'answer_value' => '3',
                ],
                [
                    'instrument_question_id' => $questions[1]->id,
                    'answer_value' => '0',
                ],
                [
                    'instrument_question_id' => $questions[2]->id,
                    'answer_value' => '',
                ],
            ]);
        });
    }
}
