<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstrumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_instrument_with_a_valid_question_contract(): void
    {
        $response = $this->postJson('/api/instruments', [
            'title' => 'Weekly Symptom Check-In',
            'description' => 'A short weekly symptom and quality of life assessment.',
            'questions' => [
                [
                    'prompt' => 'Rate your fatigue this week.',
                    'response_type' => 'scale_1_5',
                    'sort_order' => 1,
                ],
                [
                    'prompt' => 'Did you experience nausea?',
                    'response_type' => 'yes_no',
                    'sort_order' => 2,
                ],
                [
                    'prompt' => 'Anything else you want your care team to know?',
                    'response_type' => 'free_text',
                    'sort_order' => 3,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Weekly Symptom Check-In')
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonPath('data.questions.0.prompt', 'Rate your fatigue this week.')
            ->assertJsonPath('data.questions.1.response_type', 'yes_no')
            ->assertJsonPath('data.questions.2.sort_order', 3);

        $this->assertDatabaseHas('instruments', [
            'title' => 'Weekly Symptom Check-In',
        ]);

        $this->assertDatabaseHas('instrument_questions', [
            'prompt' => 'Rate your fatigue this week.',
            'response_type' => 'scale_1_5',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('instrument_questions', [
            'prompt' => 'Did you experience nausea?',
            'response_type' => 'yes_no',
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('instrument_questions', [
            'prompt' => 'Anything else you want your care team to know?',
            'response_type' => 'free_text',
            'sort_order' => 3,
        ]);
    }

    public function test_it_rejects_instrument_payloads_that_violate_the_question_contract(): void
    {
        $response = $this->postJson('/api/instruments', [
            'title' => '',
            'description' => 'Invalid instrument payload.',
            'questions' => [
                [
                    'prompt' => '',
                    'response_type' => 'maybe',
                    'sort_order' => 'first',
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'questions.0.prompt',
                'questions.0.response_type',
                'questions.0.sort_order',
            ]);
    }
}