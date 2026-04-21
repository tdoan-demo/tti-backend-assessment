<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\InstrumentQuestion;
use App\Models\Patient;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Support\AnswerValueCaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder that creates a small, coherent sample dataset for local review and manual API checks.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * The sample graph is seeded inside a transaction so local demo data is either complete or rolled back cleanly.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            // Seed one patient and one instrument so the API is usable immediately after migrate:fresh --seed.
            $patient = Patient::factory()->create([
                'name' => 'Ava Chen',
                'date_of_birth' => '1991-06-12',
                'mrn' => 'MRN-10001',
            ]);

            $instrument = Instrument::factory()->create([
                'title' => 'Weekly Symptom Check-In',
                'description' => 'A short weekly symptom and quality of life assessment.',
            ]);

            $fatigueQuestion = InstrumentQuestion::factory()
                ->for($instrument)
                ->scale()
                ->create([
                    'prompt' => 'Rate your fatigue this week.',
                    'sort_order' => 1,
                ]);

            $nauseaQuestion = InstrumentQuestion::factory()
                ->for($instrument)
                ->yesNo()
                ->create([
                    'prompt' => 'Did you experience nausea?',
                    'sort_order' => 2,
                ]);

            $notesQuestion = InstrumentQuestion::factory()
                ->for($instrument)
                ->freeText()
                ->create([
                    'prompt' => 'Anything else you want your care team to know?',
                    'sort_order' => 3,
                ]);

            // Two submissions provide deterministic sample data for summary aggregation and newest-first ordering.
            $firstSubmission = Submission::factory()
                ->for($patient)
                ->for($instrument)
                ->create([
                    'submitted_at' => Carbon::parse('2026-04-01T10:00:00Z'),
                ]);

            SubmissionAnswer::factory()
                ->for($firstSubmission, 'submission')
                ->for($fatigueQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::SCALE_1_5, 4),
                ]);

            SubmissionAnswer::factory()
                ->for($firstSubmission, 'submission')
                ->for($nauseaQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::YES_NO, true),
                ]);

            SubmissionAnswer::factory()
                ->for($firstSubmission, 'submission')
                ->for($notesQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::FREE_TEXT, 'Mild fatigue after treatment day.'),
                ]);

            $secondSubmission = Submission::factory()
                ->for($patient)
                ->for($instrument)
                ->create([
                    'submitted_at' => Carbon::parse('2026-04-08T10:00:00Z'),
                ]);

            SubmissionAnswer::factory()
                ->for($secondSubmission, 'submission')
                ->for($fatigueQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::SCALE_1_5, 3),
                ]);

            SubmissionAnswer::factory()
                ->for($secondSubmission, 'submission')
                ->for($nauseaQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::YES_NO, false),
                ]);

            SubmissionAnswer::factory()
                ->for($secondSubmission, 'submission')
                ->for($notesQuestion, 'question')
                ->create([
                    'answer_value' => AnswerValueCaster::normalizeForStorage(AnswerValueCaster::FREE_TEXT, ''),
                ]);
        });
    }
}
