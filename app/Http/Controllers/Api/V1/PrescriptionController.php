<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $prescriptions = Prescription::query()
            ->where('tenant_id', $tenant->id)
            ->with(['patient', 'doctor', 'items'])
            ->when($request->patient_id, fn ($q, $id) => $q->where('patient_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);

        return PrescriptionResource::collection($prescriptions)->toResponse($request);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $doctor = auth('api')->user();

        $prescription = DB::transaction(function () use ($request, $tenant, $doctor) {
            $prescription = Prescription::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $request->patient_id,
                'doctor_id' => $doctor->id,
                'diagnosis' => $request->diagnosis,
                'notes' => $request->notes,
                'status' => 'active',
                'issued_at' => $request->issued_at ?? now(),
                'expires_at' => $request->expires_at,
            ]);

            foreach ($request->items as $item) {
                $prescription->items()->create($item);
            }

            return $prescription;
        });

        $prescription->load(['patient', 'doctor', 'items']);

        return response()->json([
            'message' => 'Receta creada correctamente.',
            'prescription' => new PrescriptionResource($prescription),
        ], 201);
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if ((string) $prescription->tenant_id !== (string) $tenant->id) {
            return response()->json(['message' => 'Receta no encontrada.'], 404);
        }

        $prescription->load(['patient', 'doctor', 'items']);

        return response()->json(new PrescriptionResource($prescription));
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if ((string) $prescription->tenant_id !== (string) $tenant->id) {
            return response()->json(['message' => 'Receta no encontrada.'], 404);
        }

        $prescription = DB::transaction(function () use ($request, $prescription) {
            $prescription->update($request->safe()->except('items'));

            if ($request->has('items')) {
                $prescription->items()->delete();
                foreach ($request->items as $item) {
                    $prescription->items()->create($item);
                }
            }

            return $prescription;
        });

        $prescription->load(['patient', 'doctor', 'items']);

        return response()->json([
            'message' => 'Receta actualizada correctamente.',
            'prescription' => new PrescriptionResource($prescription),
        ]);
    }

    public function destroy(Request $request, Prescription $prescription): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if ((string) $prescription->tenant_id !== (string) $tenant->id) {
            return response()->json(['message' => 'Receta no encontrada.'], 404);
        }

        $prescription->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Receta anulada correctamente.',
        ]);
    }
}
