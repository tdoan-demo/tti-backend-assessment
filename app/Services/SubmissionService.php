<?php

namespace App\Services;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use App\Support\AnswerValueCaster;
use Illuminate\Support\Facades\DB;

/**
 * Domain service that persists a submission and its answers as a single unit of work.
 */
class SubmissionService
{
    /**
     * Submission creation is wrapped in a transaction so the submission row and all answer rows succeed or fail together.
     */
    public function create(Patient $patient, array $validated): Submission
    {
        /** @var Instrument $instrument */
        $instrument = Instrument::query()
            ->with('questions')
            ->findOrFail($validated['instrument_id']);

        return DB::transaction(function () use ($patient, $instrument, $validated): Submission {
            $submission = Submission::query()->create([
                'patient_id' => $patient->id,
                'instrument_id' => $instrument->id,
                'submitted_at' => $validated['submitted_at'],
            ]);

            $questionsById = $instrument->questions->keyBy('id');

            foreach ($validated['answers'] as $payload) {
                $question = $questionsById->get((int) $payload['question_id']);

                $submission->answers()->create([
                    'instrument_question_id' => $question->id,
                    'answer_value' => AnswerValueCaster::normalizeForStorage(
                        $question->response_type,
                        $payload['answer']
                    ),
                ]);
            }

            return $submission->load([
                'instrument.questions',
                'answers.question',
            ]);
        });
    }
}
