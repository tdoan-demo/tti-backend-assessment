<?php

namespace App\Services;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use App\Support\AnswerValueCaster;
use Illuminate\Support\Collection;

/**
 * Domain service that builds aggregated summary data for a patient and instrument.
 */
class PatientSummaryService
{
    /**
     * The service eagerly loads the related answers/questions once, then builds an API-ready summary payload in memory.
     */
    public function build(Patient $patient, Instrument $instrument): array
    {
        $instrument->load('questions');

        $submissions = Submission::query()
            ->where('patient_id', $patient->id)
            ->where('instrument_id', $instrument->id)
            ->with(['answers.question'])
            ->orderBy('submitted_at')
            ->get();

        $firstSubmission = $submissions->first();
        $lastSubmission = $submissions->last();

        return [
            'patient_id' => $patient->id,
            'instrument' => [
                'id' => $instrument->id,
                'title' => $instrument->title,
                'description' => $instrument->description,
            ],
            'total_submissions' => $submissions->count(),
            'date_range' => [
                'earliest' => $firstSubmission?->submitted_at?->toIso8601String(),
                'latest' => $lastSubmission?->submitted_at?->toIso8601String(),
            ],
            'questions' => $instrument->questions
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($question) => $this->summarizeQuestion($question, $submissions))
                ->all(),
        ];
    }

    /**
     * Each question is aggregated independently so summary output stays aligned with the original instrument structure.
     */
    protected function summarizeQuestion($question, Collection $submissions): array
    {
        $answers = $submissions
            ->flatMap(fn ($submission) => $submission->answers)
            ->filter(fn ($answer) => $answer->instrument_question_id === $question->id)
            ->values();

        $summary = match ($question->response_type) {
            AnswerValueCaster::SCALE_1_5 => [
                'average_score' => $answers->isEmpty()
                    ? null
                    : round($answers->avg(fn ($answer) => (int) $answer->answer_value), 2),
            ],
            AnswerValueCaster::YES_NO => [
                'yes_percentage' => $answers->isEmpty()
                    ? null
                    : round(
                        ($answers->filter(fn ($answer) => AnswerValueCaster::toBool($answer->answer_value))->count() / $answers->count()) * 100,
                        2
                    ),
            ],
            AnswerValueCaster::FREE_TEXT => [
                'non_empty_response_count' => $answers
                    ->filter(fn ($answer) => trim((string) $answer->answer_value) !== '')
                    ->count(),
            ],
            default => [],
        };

        return [
            'question_id' => $question->id,
            'prompt' => $question->prompt,
            'response_type' => $question->response_type,
            'sort_order' => $question->sort_order,
            'summary' => $summary,
        ];
    }
}
