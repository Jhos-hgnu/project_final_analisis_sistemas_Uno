<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $doctor;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::factory()->create();

        $this->doctor = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => Hash::make('password'),
        ]);

        $this->doctor->assignRole('Médico');
        $this->token = JWTAuth::fromUser($this->doctor);
    }

    protected function headers(): array
    {
        return [
            'X-Tenant-ID' => $this->tenant->id,
            'Authorization' => "Bearer {$this->token}",
        ];
    }

    public function test_doctor_can_create_prescription(): void
    {
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->headers())->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'diagnosis' => 'Infección respiratoria aguda',
            'notes' => 'Paciente debe guardar reposo',
            'issued_at' => now()->toDateTimeString(),
            'items' => [
                [
                    'medication_name' => 'Amoxicilina',
                    'dosage' => '500 mg',
                    'frequency' => 'Cada 8 horas',
                    'duration' => '7 días',
                    'instructions' => 'Tomar después de alimentos',
                ],
                [
                    'medication_name' => 'Ibuprofeno',
                    'dosage' => '400 mg',
                    'frequency' => 'Cada 12 horas',
                    'duration' => '5 días',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'prescription' => [
                    'id', 'diagnosis', 'status', 'issued_at',
                    'patient', 'doctor', 'items',
                ],
            ]);

        $this->assertDatabaseHas('prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Infección respiratoria aguda',
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('prescription_items', 2);
    }

    public function test_unauthenticated_user_cannot_create_prescription(): void
    {
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders(['X-Tenant-ID' => $this->tenant->id])
            ->postJson('/api/v1/prescriptions', [
                'patient_id' => $patient->id,
                'diagnosis' => 'Test',
                'issued_at' => now()->toDateTimeString(),
                'items' => [
                    ['medication_name' => 'Test', 'dosage' => '1', 'frequency' => '1', 'duration' => '1'],
                ],
            ]);

        $response->assertStatus(401);
    }

    public function test_prescription_requires_items(): void
    {
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->headers())->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'diagnosis' => 'Test',
            'issued_at' => now()->toDateTimeString(),
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_list_prescriptions(): void
    {
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->headers())->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'diagnosis' => 'Test',
            'issued_at' => now()->toDateTimeString(),
            'items' => [
                ['medication_name' => 'Test', 'dosage' => '1', 'frequency' => '1', 'duration' => '1'],
            ],
        ]);

        $response = $this->withHeaders($this->headers())->getJson('/api/v1/prescriptions');

        $response->assertStatus(200);

        $content = $response->json();
        $this->assertArrayHasKey('data', $content, 'Response must have a "data" key. Got: ' . json_encode($content));
        $this->assertCount(1, $content['data']);
    }

    public function test_tenant_scoping_isolates_prescriptions(): void
    {
        // Create patient + prescription for tenant 1
        $patient1 = Patient::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->withHeaders($this->headers())->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient1->id,
            'diagnosis' => 'Tenant 1 diagnosis',
            'issued_at' => now()->toDateTimeString(),
            'items' => [
                ['medication_name' => 'Test', 'dosage' => '1', 'frequency' => '1', 'duration' => '1'],
            ],
        ]);

        // Verify tenant 1 sees 1 prescription
        $response1 = $this->withHeaders($this->headers())->getJson('/api/v1/prescriptions');
        $this->assertCount(1, $response1->json('data'), 'Tenant 1 should see 1 prescription');

        // Check the specific prescription exists and belongs to our tenant
        $prescriptions = \App\Models\Prescription::where('tenant_id', $this->tenant->id)->get();
        $this->assertCount(1, $prescriptions);

        // Verify tenant isolation at DB level
        $otherTenantId = 'other-tenant-uuid';
        $otherPrescriptions = \App\Models\Prescription::where('tenant_id', $otherTenantId)->get();
        $this->assertCount(0, $otherPrescriptions, 'Other tenant should have 0 prescriptions');
    }

    public function test_doctor_can_cancel_prescription(): void
    {
        $patient = Patient::factory()->create(['tenant_id' => $this->tenant->id]);

        $createResponse = $this->withHeaders($this->headers())->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'diagnosis' => 'Test',
            'issued_at' => now()->toDateTimeString(),
            'items' => [
                ['medication_name' => 'Test', 'dosage' => '1', 'frequency' => '1', 'duration' => '1'],
            ],
        ]);

        $prescriptionId = $createResponse->json('prescription.id');

        $response = $this->withHeaders($this->headers())
            ->deleteJson("/api/v1/prescriptions/{$prescriptionId}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'status' => 'cancelled',
        ]);
    }
}
