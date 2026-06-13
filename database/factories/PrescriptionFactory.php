<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'diagnosis' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => fake()->optional(0.5)->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}
