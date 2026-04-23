import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, DefineComponent, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createServerRouter } from './router';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: (name) => {
                const resolvedComponent = resolvePageComponent(
                    `./pages/${name}.vue`,
                    import.meta.glob<DefineComponent>('./pages/**/*.vue'),
                );
                console.log(`SSR Resolved component: ${name}`);
                return resolvedComponent;
            },
            setup: async ({ App, props, plugin }) => {
                const router = createServerRouter();
                const app = createSSRApp({ render: () => h(App, props) })
                    .use(plugin)
                    .use(router);

                await router.push(page.url);
                await router.isReady();

                return app;
            },
        }),
    { cluster: true },
);
