<template>
    <section class="prescriptions">
        <header class="prescriptions__header">
            <h2 class="prescriptions__title">Recetas médicas</h2>
            <router-link
                v-if="canCreate"
                class="prescriptions__create"
                :to="{ name: 'prescriptions.create' }"
            >
                Nueva receta
            </router-link>
        </header>

        <div v-if="loading" class="prescriptions__loading">
            Cargando recetas…
        </div>

        <div v-else-if="prescriptions.length === 0" class="prescriptions__empty">
            No hay recetas registradas.
        </div>

        <table v-else class="prescriptions__table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Diagnóstico</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in prescriptions" :key="p.id">
                    <td>{{ p.patient?.name ?? '—' }}</td>
                    <td>{{ p.doctor?.name ?? '—' }}</td>
                    <td class="prescriptions__diagnosis">{{ p.diagnosis }}</td>
                    <td>
                        <span :class="['prescriptions__badge', `prescriptions__badge--${p.status}`]">
                            {{ statusLabel(p.status) }}
                        </span>
                    </td>
                    <td>{{ formatDate(p.issued_at) }}</td>
                    <td class="prescriptions__actions">
                        <router-link
                            :to="{ name: 'prescriptions.show', params: { id: p.id } }"
                            class="prescriptions__action"
                        >
                            Ver
                        </router-link>
                        <router-link
                            v-if="canEdit"
                            :to="{ name: 'prescriptions.edit', params: { id: p.id } }"
                            class="prescriptions__action"
                        >
                            Editar
                        </router-link>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="pagination.last_page > 1" class="prescriptions__pagination">
            <button
                :disabled="pagination.current_page <= 1"
                @click="goToPage(pagination.current_page - 1)"
            >
                Anterior
            </button>
            <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
            <button
                :disabled="pagination.current_page >= pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
            >
                Siguiente
            </button>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { usePrescriptionStore } from '@/stores/prescriptions';

const store = usePrescriptionStore();

const prescriptions = computed(() => store.prescriptions);
const loading = computed(() => store.loading);
const pagination = computed(() => store.pagination);

const user = computed(() => {
    try {
        return JSON.parse(localStorage.getItem('auth_user') ?? 'null');
    } catch {
        return null;
    }
});

const canCreate = computed(() => {
    return user.value?.roles?.some(r => r.name === 'Médico' || r.name === 'Admin') ?? false;
});

const canEdit = canCreate;

function statusLabel(status) {
    const map = { active: 'Activa', completed: 'Completada', cancelled: 'Anulada' };

    return map[status] ?? status;
}

function formatDate(date) {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function goToPage(page) {
    store.fetchPrescriptions({ page });
}

onMounted(() => {
    store.fetchPrescriptions();
});
</script>

<style scoped>
.prescriptions__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.prescriptions__title {
    font-size: 1.25rem;
    font-weight: 700;
}

.prescriptions__create {
    background: #2563eb;
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.prescriptions__table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.prescriptions__table th,
.prescriptions__table td {
    text-align: left;
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
}

.prescriptions__table th {
    background: #f1f5f9;
    font-weight: 600;
}

.prescriptions__diagnosis {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.prescriptions__badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.prescriptions__badge--active {
    background: #dcfce7;
    color: #166534;
}

.prescriptions__badge--completed {
    background: #dbeafe;
    color: #1e40af;
}

.prescriptions__badge--cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.prescriptions__actions {
    display: flex;
    gap: 0.5rem;
}

.prescriptions__action {
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
}

.prescriptions__loading,
.prescriptions__empty {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}

.prescriptions__pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.prescriptions__pagination button {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
}

.prescriptions__pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
