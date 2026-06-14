<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'medication_name' => fake()->randomElement([
                'Paracetamol 500mg',
                'Ibuprofeno 400mg',
                'Amoxicilina 500mg',
                'Omeprazol 20mg',
                'Loratadina 10mg',
                'Azitromicina 500mg',
                'Diclofenaco 75mg',
                'Metformina 850mg',
            ]),
            'dosage' => fake()->randomElement(['500 mg', '250 mg', '1 g', '10 mg', '20 mg']),
            'frequency' => fake()->randomElement(['Cada 8 horas', 'Cada 12 horas', 'Cada 24 horas', 'Cada 6 horas']),
            'duration' => fake()->randomElement(['7 días', '10 días', '14 días', '5 días']),
            'instructions' => fake()->optional(0.7)->sentence(),
        ];
    }
}
