export function authGuard(to, from, next) {
    const token = localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && ! token) {
        next({ name: 'login' });

        return;
    }

    if (to.meta.guest && token) {
        next({ name: 'home' });

        return;
    }

    if (to.meta.role && token) {
        try {
            const user = JSON.parse(localStorage.getItem('auth_user') ?? 'null');
            const hasRole = user?.roles?.some(r => r.name === to.meta.role) ?? false;

            if (! hasRole) {
                next({ name: 'home' });

                return;
            }
        } catch {
            next({ name: 'home' });

            return;
        }
    }

    next();
}
