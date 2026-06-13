import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
import PrescriptionListPage from '@/modules/prescriptions/pages/PrescriptionListPage.vue';
import PrescriptionFormPage from '@/modules/prescriptions/pages/PrescriptionFormPage.vue';
import PrescriptionDetailPage from '@/modules/prescriptions/pages/PrescriptionDetailPage.vue';
import { authGuard } from '@/router/guards';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomePage,
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guest: true },
        },
        {
            path: '/prescriptions',
            name: 'prescriptions.index',
            component: PrescriptionListPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/prescriptions/create',
            name: 'prescriptions.create',
            component: PrescriptionFormPage,
            meta: { requiresAuth: true, role: 'Médico' },
        },
        {
            path: '/prescriptions/:id',
            name: 'prescriptions.show',
            component: PrescriptionDetailPage,
            meta: { requiresAuth: true },
        },
        {
            path: '/prescriptions/:id/edit',
            name: 'prescriptions.edit',
            component: PrescriptionFormPage,
            meta: { requiresAuth: true, role: 'Médico' },
        },
    ],
});

router.beforeEach(authGuard);

export default router;
