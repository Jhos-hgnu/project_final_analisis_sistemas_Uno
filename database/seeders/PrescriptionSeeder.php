<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PrescriptionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tenant = Tenant::query()->first();

        if ($tenant === null) {
            return;
        }

        $doctor = User::query()
            ->where('tenant_id', $tenant->id)
            ->role('Médico')
            ->first();

        if ($doctor === null) {
            $doctor = User::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Dr. José Pérez',
                'email' => 'medico@hospital.com',
                'password' => Hash::make('password'),
            ]);

            $doctor->assignRole('Médico');
        }

        $patients = Patient::factory()
            ->count(10)
            ->create(['tenant_id' => $tenant->id]);

        foreach ($patients as $patient) {
            Prescription::factory()
                ->count(2)
                ->create([
                    'tenant_id' => $tenant->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                ])
                ->each(function (Prescription $prescription): void {
                    PrescriptionItem::factory()
                        ->count(rand(2, 4))
                        ->create(['prescription_id' => $prescription->id]);
                });
        }

        $this->command?->info('Se crearon 10 pacientes con 2 recetas cada uno.');
    }
}
