import {
    createMemoryHistory,
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from 'vue-router';

const routes: RouteRecordRaw[] = [
    {
        path: '/index',
        name: 'index.home',
        component: () => import('@/pages/NewsFeed.vue'),
        meta: { title: 'News Feed' },
    },
    {
        path: '/friends',
        name: 'index.friends',
        component: () => import('@/views/FriendsView.vue'),
        meta: { title: 'Friends' },
    },
    {
        path: '/watch',
        name: 'index.watch',
        component: () => import('@/views/WatchView.vue'),
        meta: { title: 'Watch' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'fallback',
        component: { template: '<div />' },
    },
];

export function createClientRouter() {
    const router = createRouter({
        history: createWebHistory(),
        routes,
    });

    router.afterEach((to) => {
        if (to.meta && to.meta.title) {
            document.title =
                to.meta.title +
                ' - ' +
                (import.meta.env.VITE_APP_NAME || 'Laravel');
        }
    });

    return router;
}

export function createServerRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes,
    });
}
