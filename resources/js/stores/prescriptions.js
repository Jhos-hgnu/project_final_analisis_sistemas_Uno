import { defineStore } from 'pinia';
import api from '@/plugins/axios';

export const usePrescriptionStore = defineStore('prescriptions', {
    state: () => ({
        prescriptions: [],
        currentPrescription: null,
        loading: false,
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
    }),
    actions: {
        async fetchPrescriptions(params = {}) {
            this.loading = true;

            try {
                const { data } = await api.get('/prescriptions', { params });

                this.prescriptions = data.data ?? [];
                this.pagination = {
                    current_page: data.meta?.current_page ?? 1,
                    last_page: data.meta?.last_page ?? 1,
                    total: data.meta?.total ?? 0,
                };
            } catch {
                this.prescriptions = [];
            } finally {
                this.loading = false;
            }
        },
        async fetchPrescription(id) {
            this.loading = true;

            try {
                const { data } = await api.get(`/prescriptions/${id}`);

                this.currentPrescription = data.data ?? data;
            } catch {
                this.currentPrescription = null;
            } finally {
                this.loading = false;
            }
        },
        async createPrescription(payload) {
            const { data } = await api.post('/prescriptions', payload);

            return data;
        },
        async updatePrescription(id, payload) {
            const { data } = await api.put(`/prescriptions/${id}`, payload);

            return data;
        },
        async deletePrescription(id) {
            const { data } = await api.delete(`/prescriptions/${id}`);

            return data;
        },
    },
});
