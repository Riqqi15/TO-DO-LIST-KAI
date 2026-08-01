import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

createInertiaApp({
    title: (title) => `${title} - To Do List KAI`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');

        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
