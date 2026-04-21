<?php

namespace App\Http\Resources;

use App\Support\AnswerValueCaster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing submission answers with typed output values.
 */
class SubmissionAnswerResource extends JsonResource
{
    /**
     * The answer is cast back to the consumer-facing type so API clients do not need to interpret the storage format.
     */
    public function toArray(Request $request): array
    {
        $responseType = $this->relationLoaded('question') ? $this->question->response_type : null;

        return [
            'id' => $this->id,
            'question_id' => $this->instrument_question_id,
            'prompt' => $this->relationLoaded('question') ? $this->question->prompt : null,
            'response_type' => $responseType,
            'answer' => AnswerValueCaster::castForOutput($responseType, $this->answer_value),
        ];
    }
}
