<template>
    <section class="form">
        <h2 class="form__title">
            {{ isEdit ? 'Editar receta' : 'Nueva receta' }}
        </h2>

        <form class="form__fields" @submit.prevent="handleSubmit">
            <label class="form__label">
                Paciente
                <select v-model="form.patient_id" class="form__input" required>
                    <option value="" disabled>Seleccione un paciente</option>
                    <option v-for="p in patients" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>
            </label>

            <label class="form__label">
                Diagnóstico
                <textarea
                    v-model="form.diagnosis"
                    class="form__input form__textarea"
                    required
                />
            </label>

            <label class="form__label">
                Notas <small>(opcional)</small>
                <textarea v-model="form.notes" class="form__input form__textarea" />
            </label>

            <label class="form__label">
                Fecha de emisión
                <input v-model="form.issued_at" class="form__input" type="date" required />
            </label>

            <fieldset class="form__items">
                <legend class="form__items-title">Medicamentos</legend>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="form__item-row"
                >
                    <input
                        v-model="item.medication_name"
                        class="form__input"
                        placeholder="Medicamento"
                        required
                    >
                    <input
                        v-model="item.dosage"
                        class="form__input form__input--sm"
                        placeholder="Dosis"
                        required
                    >
                    <input
                        v-model="item.frequency"
                        class="form__input form__input--sm"
                        placeholder="Frecuencia"
                        required
                    >
                    <input
                        v-model="item.duration"
                        class="form__input form__input--sm"
                        placeholder="Duración"
                        required
                    >
                    <button
                        type="button"
                        class="form__remove"
                        @click="removeItem(index)"
                    >
                        ×
                    </button>
                </div>

                <button type="button" class="form__add" @click="addItem">
                    + Agregar medicamento
                </button>
            </fieldset>

            <p v-if="errorMessage" class="form__error">
                {{ errorMessage }}
            </p>

            <div class="form__actions">
                <router-link class="form__cancel" :to="{ name: 'prescriptions.index' }">
                    Cancelar
                </router-link>
                <button class="form__submit" type="submit" :disabled="saving">
                    {{ saving ? 'Guardando…' : 'Guardar receta' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePrescriptionStore } from '@/stores/prescriptions';
import api from '@/plugins/axios';

const route = useRoute();
const router = useRouter();
const store = usePrescriptionStore();

const isEdit = computed(() => Boolean(route.params.id));
const saving = ref(false);
const errorMessage = ref('');
const patients = ref([]);

const form = ref({
    patient_id: '',
    diagnosis: '',
    notes: '',
    issued_at: new Date().toISOString().split('T')[0],
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

function addItem() {
    form.value.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removeItem(index) {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
}

async function loadPatients() {
    try {
        const { data } = await api.get('/patients', { params: { per_page: 100 } });

        patients.value = data.data ?? data ?? [];
    } catch {
        patients.value = [];
    }
}

async function loadPrescription() {
    if (!isEdit.value) {
        return;
    }

    try {
        await store.fetchPrescription(route.params.id);

        if (store.currentPrescription) {
            const p = store.currentPrescription;

            form.value = {
                patient_id: p.patient?.id ?? '',
                diagnosis: p.diagnosis ?? '',
                notes: p.notes ?? '',
                issued_at: p.issued_at ? p.issued_at.split('T')[0] : '',
                items: p.items?.map(i => ({
                    medication_name: i.medication_name,
                    dosage: i.dosage,
                    frequency: i.frequency,
                    duration: i.duration,
                    instructions: i.instructions ?? '',
                })) ?? [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
            };
        }
    } catch {
        errorMessage.value = 'No se pudo cargar la receta.';
    }
}

async function handleSubmit() {
    saving.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            patient_id: Number(form.value.patient_id),
            diagnosis: form.value.diagnosis,
            notes: form.value.notes || null,
            issued_at: form.value.issued_at,
            items: form.value.items.map(item => ({
                medication_name: item.medication_name,
                dosage: item.dosage,
                frequency: item.frequency,
                duration: item.duration,
                instructions: item.instructions || null,
            })),
        };

        if (isEdit.value) {
            await store.updatePrescription(route.params.id, payload);
        } else {
            await store.createPrescription(payload);
        }

        await router.push({ name: 'prescriptions.index' });
    } catch (error) {
        const message = error?.response?.data?.message
            ?? error?.response?.data?.errors?.[Object.keys(error?.response?.data?.errors ?? {})[0]]?.[0]
            ?? 'No se pudo guardar la receta.';
        errorMessage.value = message;
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    loadPatients();

    if (isEdit.value) {
        loadPrescription();
    }
});
</script>

<style scoped>
.form {
    max-width: 720px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.form__title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.form__fields {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.form__label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.9rem;
    color: #334155;
}

.form__input {
    border: 1px solid #cbd5f5;
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
    font-size: 1rem;
}

.form__input--sm {
    flex: 1;
    min-width: 0;
}

.form__textarea {
    min-height: 80px;
    resize: vertical;
}

.form__items {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form__items-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: #334155;
}

.form__item-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form__remove {
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    font-size: 1.2rem;
    cursor: pointer;
    color: #991b1b;
    flex-shrink: 0;
}

.form__add {
    background: none;
    border: 1px dashed #94a3b8;
    border-radius: 8px;
    padding: 0.5rem;
    cursor: pointer;
    color: #2563eb;
    font-weight: 500;
    font-size: 0.9rem;
}

.form__error {
    color: #b91c1c;
    font-size: 0.9rem;
}

.form__actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

.form__cancel {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.65rem 1rem;
    text-decoration: none;
    color: #334155;
    font-weight: 500;
}

.form__submit {
    border: none;
    border-radius: 8px;
    padding: 0.65rem 1rem;
    background: #2563eb;
    color: #ffffff;
    font-weight: 600;
    cursor: pointer;
}

.form__submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
