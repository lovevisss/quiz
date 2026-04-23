import '../css/app.css';

import axios from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import { createClientRouter } from './router';
import store from './store';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const router = createClientRouter();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ).then((component) => {
            console.log(`Client Resolved component: ${name}`);
            return component as DefineComponent;
        }),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(router)
            .use(store);

        // Hydrate user on app boot via API
        store.dispatch('fetchUser').catch((err) => {
            console.error('Failed to fetch user during app boot:', err);
        });
        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
