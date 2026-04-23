import {
    createMemoryHistory,
    createRouter,
    type RouteRecordRaw,
} from 'vue-router';

const routes: RouteRecordRaw[] = [
    {
        path: '/index',
        name: 'index.home',
        component: () => import('../pages/NewsFeed.vue'),
        meta: { title: 'News Feed' },
    },
    {
        path: '/quiz',
        name: 'quiz.home',
        component: () => import('../pages/Quiz/ActivityHome.vue'),
        meta: { title: 'Activity Home' },
    },
    {
        path: '/quiz/question',
        name: 'quiz.question',
        component: () => import('../pages/Quiz/Question.vue'),
        meta: { title: 'Quiz Question' },
    },
    {
        path: '/quiz/result',
        name: 'quiz.result',
        component: () => import('../pages/Quiz/Result.vue'),
        meta: { title: 'Quiz Result' },
    },
    {
        path: '/quiz/leaderboard',
        name: 'quiz.leaderboard',
        component: () => import('../pages/Quiz/Leaderboard.vue'),
        meta: { title: 'Leaderboard' },
    },
    {
        path: '/quiz/certificate',
        name: 'quiz.certificate',
        component: () => import('../pages/Quiz/Certificate.vue'),
        meta: { title: 'Certificate' },
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
        history: createMemoryHistory(),
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
