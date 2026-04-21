<?php

namespace Tests\Feature;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use App\Support\AnswerValueCaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_submission_when_not_all_questions_are_answered(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $response = $this->postJson("/api/patients/{$patient->id}/submissions", [
            'instrument_id' => $instrument->id,
            'submitted_at' => '2026-04-19T19:30:00Z',
            'answers' => [
                [
                    'question_id' => $questions[0]->id,
                    'answer' => 4,
                ],
                [
                    'question_id' => $questions[1]->id,
                    'answer' => true,
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_it_rejects_a_submission_when_an_answer_does_not_match_its_question_response_type(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $response = $this->postJson("/api/patients/{$patient->id}/submissions", [
            'instrument_id' => $instrument->id,
            'submitted_at' => '2026-04-19T19:30:00Z',
            'answers' => [
                [
                    'question_id' => $questions[0]->id,
                    'answer' => 6,
                ],
                [
                    'question_id' => $questions[1]->id,
                    'answer' => true,
                ],
                [
                    'question_id' => $questions[2]->id,
                    'answer' => 'Still tired this week.',
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.answer']);
    }

    public function test_it_creates_a_valid_submission(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $response = $this->postJson("/api/patients/{$patient->id}/submissions", [
            'instrument_id' => $instrument->id,
            'submitted_at' => '2026-04-19T19:30:00Z',
            'answers' => [
                [
                    'question_id' => $questions[0]->id,
                    'answer' => 5,
                ],
                [
                    'question_id' => $questions[1]->id,
                    'answer' => true,
                ],
                [
                    'question_id' => $questions[2]->id,
                    'answer' => 'Energy improved this week.',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.answers.0.answer', 5)
            ->assertJsonPath('data.answers.1.answer', true);

        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseCount('submission_answers', 3);
    }

    public function test_it_accepts_an_empty_free_text_answer(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $response = $this->postJson("/api/patients/{$patient->id}/submissions", [
            'instrument_id' => $instrument->id,
            'submitted_at' => '2026-04-19T19:30:00Z',
            'answers' => [
                [
                    'question_id' => $questions[0]->id,
                    'answer' => 3,
                ],
                [
                    'question_id' => $questions[1]->id,
                    'answer' => false,
                ],
                [
                    'question_id' => $questions[2]->id,
                    'answer' => '',
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('submission_answers', [
            'instrument_question_id' => $questions[2]->id,
            'answer_value' => '',
        ]);
    }

    public function test_it_lists_submissions_newest_first(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $olderSubmission = $this->createSubmission(
            $patient,
            $instrument,
            $questions,
            [2, false, 'First check-in'],
            '2026-04-01 10:00:00'
        );

        $newerSubmission = $this->createSubmission(
            $patient,
            $instrument,
            $questions,
            [4, true, 'Second check-in'],
            '2026-04-08 10:00:00'
        );

        $response = $this->getJson("/api/patients/{$patient->id}/submissions");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerSubmission->id)
            ->assertJsonPath('data.1.id', $olderSubmission->id);
    }

    public function test_it_returns_404_when_fetching_a_submission_for_the_wrong_patient(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $submission = $this->createSubmission(
            $patient,
            $instrument,
            $questions,
            [4, true, 'Scoped correctly'],
            '2026-04-08 10:00:00'
        );

        $otherPatient = Patient::query()->create([
            'name' => 'Noah Patel',
            'date_of_birth' => '1988-03-21',
            'mrn' => 'MRN-10002',
        ]);

        $response = $this->getJson("/api/patients/{$otherPatient->id}/submissions/{$submission->id}");

        $response->assertNotFound();
    }

    public function test_it_returns_a_submission_summary(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $this->createSubmission(
            $patient,
            $instrument,
            $questions,
            [4, true, 'Some fatigue'],
            '2026-04-01 10:00:00'
        );

        $this->createSubmission(
            $patient,
            $instrument,
            $questions,
            [2, false, ''],
            '2026-04-08 10:00:00'
        );

        $response = $this->getJson("/api/patients/{$patient->id}/summary?instrument_id={$instrument->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.total_submissions', 2)
            ->assertJsonPath('data.questions.0.summary.average_score', 3)
            ->assertJsonPath('data.questions.1.summary.yes_percentage', 50)
            ->assertJsonPath('data.questions.2.summary.non_empty_response_count', 1);
    }

    public function test_it_rejects_a_submission_when_a_question_does_not_belong_to_the_selected_instrument(): void
    {
        [$patient, $instrument, $questions] = $this->seedReferenceData();

        $otherInstrument = Instrument::query()->create([
            'title' => 'Other Instrument',
            'description' => 'Used to test question scoping.',
        ]);

        $foreignQuestion = $otherInstrument->questions()->create([
            'prompt' => 'Foreign question',
            'response_type' => AnswerValueCaster::FREE_TEXT,
            'sort_order' => 1,
        ]);

        $response = $this->postJson("/api/patients/{$patient->id}/submissions", [
            'instrument_id' => $instrument->id,
            'submitted_at' => '2026-04-19T19:30:00Z',
            'answers' => [
                [
                    'question_id' => $questions[0]->id,
                    'answer' => 3,
                ],
                [
                    'question_id' => $questions[1]->id,
                    'answer' => true,
                ],
                [
                    'question_id' => $foreignQuestion->id,
                    'answer' => 'wrong instrument question',
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    protected function seedReferenceData(): array
    {
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

        return [$patient, $instrument, $questions];
    }

    protected function createSubmission(
        Patient $patient,
        Instrument $instrument,
        Collection $questions,
        array $answers,
        string $submittedAt
    ): Submission {
        $submission = Submission::query()->create([
            'patient_id' => $patient->id,
            'instrument_id' => $instrument->id,
            'submitted_at' => $submittedAt,
        ]);

        $submission->answers()->createMany(
            $questions->values()->map(function ($question, int $index) use ($answers) {
                return [
                    'instrument_question_id' => $question->id,
                    'answer_value' => AnswerValueCaster::normalizeForStorage(
                        $question->response_type,
                        $answers[$index]
                    ),
                ];
            })->all()
        );

        return $submission;
    }
}