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
    },
    {
        path: '/friends',
        name: 'index.friends',
        component: () => import('@/views/FriendsView.vue'),
    },
    {
        path: '/watch',
        name: 'index.watch',
        component: () => import('@/views/WatchView.vue'),
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'fallback',
        component: { template: '<div />' },
    },
];

export function createClientRouter() {
    return createRouter({
        history: createWebHistory(),
        routes,
    });
}

export function createServerRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes,
    });
}
