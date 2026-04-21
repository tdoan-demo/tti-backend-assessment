<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests covering the patient creation contract.
 */
class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_patient_with_a_valid_contract_payload(): void
    {
        $response = $this->postJson('/api/patients', [
            'name' => 'Jane Doe',
            'date_of_birth' => '1990-05-14',
            'mrn' => 'MRN-10001',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.date_of_birth', '1990-05-14')
            ->assertJsonPath('data.mrn', 'MRN-10001');

        $this->assertDatabaseHas('patients', [
            'name' => 'Jane Doe',
            'mrn' => 'MRN-10001',
        ]);

        $patient = Patient::query()->where('mrn', 'MRN-10001')->firstOrFail();

        $this->assertSame('Jane Doe', $patient->name);
        $this->assertSame('MRN-10001', $patient->mrn);
        $this->assertSame('1990-05-14', $patient->date_of_birth->toDateString());
    }

    public function test_it_rejects_patient_payloads_that_violate_the_contract(): void
    {
        $response = $this->postJson('/api/patients', [
            'name' => '',
            'date_of_birth' => 'not-a-date',
            'mrn' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'date_of_birth',
                'mrn',
            ]);
    }

    public function test_it_rejects_duplicate_mrns(): void
    {
        Patient::factory()->create([
            'name' => 'Existing Patient',
            'date_of_birth' => '1988-08-21',
            'mrn' => 'MRN-10001',
        ]);

        $response = $this->postJson('/api/patients', [
            'name' => 'John Doe',
            'date_of_birth' => '1988-08-21',
            'mrn' => 'MRN-10001',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mrn']);
    }
}
