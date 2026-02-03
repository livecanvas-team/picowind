import { createWebHashHistory, createRouter } from 'vue-router'

declare global {
    interface Window {
        picowind: {
            site_meta: {
                web_history: string;
            };
        };
    }
}

const router = createRouter({
    history: createWebHashHistory(window.picowind?.site_meta?.web_history || ''),
    scrollBehavior(_, _2, savedPosition) {
        return savedPosition || { left: 0, top: 0 };
    },
    routes: [
        { path: '/', name: 'home', redirect: { name: 'onboarding' } },
        {
            path: '/onboarding',
            name: 'onboarding',
            component: () => import('./pages/Onboarding.vue'),
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'NotFound',
            redirect: { name: 'onboarding' },
        },
    ],
});

export default router;
