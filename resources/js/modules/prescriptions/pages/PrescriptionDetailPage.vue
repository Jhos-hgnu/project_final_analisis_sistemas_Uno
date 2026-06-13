<template>
    <section class="detail">
        <div v-if="loading" class="detail__loading">
            Cargando receta…
        </div>

        <div v-else-if="!prescription" class="detail__empty">
            Receta no encontrada.
        </div>

        <template v-else>
            <header class="detail__header">
                <h2 class="detail__title">Receta #{{ prescription.id }}</h2>
                <div class="detail__header-actions">
                    <router-link
                        v-if="canEdit"
                        class="detail__edit"
                        :to="{ name: 'prescriptions.edit', params: { id: prescription.id } }"
                    >
                        Editar
                    </router-link>
                    <router-link
                        class="detail__back"
                        :to="{ name: 'prescriptions.index' }"
                    >
                        Volver
                    </router-link>
                </div>
            </header>

            <div class="detail__info">
                <div class="detail__info-row">
                    <span class="detail__label">Paciente:</span>
                    <span class="detail__value">{{ prescription.patient?.name ?? '—' }}</span>
                </div>
                <div class="detail__info-row">
                    <span class="detail__label">Médico:</span>
                    <span class="detail__value">{{ prescription.doctor?.name ?? '—' }}</span>
                </div>
                <div class="detail__info-row">
                    <span class="detail__label">Estado:</span>
                    <span :class="['detail__badge', `detail__badge--${prescription.status}`]">
                        {{ statusLabel(prescription.status) }}
                    </span>
                </div>
                <div class="detail__info-row">
                    <span class="detail__label">Fecha de emisión:</span>
                    <span class="detail__value">{{ formatDate(prescription.issued_at) }}</span>
                </div>
                <div class="detail__info-row">
                    <span class="detail__label">Válida hasta:</span>
                    <span class="detail__value">{{ formatDate(prescription.expires_at) ?? '—' }}</span>
                </div>
            </div>

            <div class="detail__section">
                <h3 class="detail__section-title">Diagnóstico</h3>
                <p class="detail__text">{{ prescription.diagnosis }}</p>
            </div>

            <div v-if="prescription.notes" class="detail__section">
                <h3 class="detail__section-title">Notas</h3>
                <p class="detail__text">{{ prescription.notes }}</p>
            </div>

            <div class="detail__section">
                <h3 class="detail__section-title">Medicamentos recetados</h3>
                <table class="detail__items-table">
                    <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Dosis</th>
                            <th>Frecuencia</th>
                            <th>Duración</th>
                            <th>Instrucciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in prescription.items" :key="item.id">
                            <td>{{ item.medication_name }}</td>
                            <td>{{ item.dosage }}</td>
                            <td>{{ item.frequency }}</td>
                            <td>{{ item.duration }}</td>
                            <td>{{ item.instructions ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { usePrescriptionStore } from '@/stores/prescriptions';

const route = useRoute();
const store = usePrescriptionStore();

const loading = computed(() => store.loading);
const prescription = computed(() => store.currentPrescription);

const user = computed(() => {
    try {
        return JSON.parse(localStorage.getItem('auth_user') ?? 'null');
    } catch {
        return null;
    }
});

const canEdit = computed(() => {
    return user.value?.roles?.some(r => r.name === 'Médico' || r.name === 'Admin') ?? false;
});

function statusLabel(status) {
    const map = { active: 'Activa', completed: 'Completada', cancelled: 'Anulada' };

    return map[status] ?? status;
}

function formatDate(date) {
    if (!date) {
        return null;
    }

    return new Date(date).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

onMounted(() => {
    store.fetchPrescription(route.params.id);
});
</script>

<style scoped>
.detail__loading,
.detail__empty {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}

.detail__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.detail__title {
    font-size: 1.25rem;
    font-weight: 700;
}

.detail__header-actions {
    display: flex;
    gap: 0.5rem;
}

.detail__edit {
    background: #2563eb;
    color: #ffffff;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
}

.detail__back {
    border: 1px solid #e2e8f0;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    text-decoration: none;
    color: #334155;
    font-weight: 500;
    font-size: 0.85rem;
}

.detail__info {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.detail__info-row {
    display: flex;
    gap: 0.5rem;
}

.detail__label {
    font-weight: 600;
    color: #475569;
    min-width: 130px;
}

.detail__value {
    color: #0f172a;
}

.detail__badge {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.detail__badge--active {
    background: #dcfce7;
    color: #166534;
}

.detail__badge--completed {
    background: #dbeafe;
    color: #1e40af;
}

.detail__badge--cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.detail__section {
    margin-bottom: 1rem;
}

.detail__section-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #334155;
}

.detail__text {
    color: #475569;
    line-height: 1.6;
}

.detail__items-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.detail__items-table th,
.detail__items-table td {
    text-align: left;
    padding: 0.6rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.85rem;
}

.detail__items-table th {
    background: #f1f5f9;
    font-weight: 600;
}
</style>
