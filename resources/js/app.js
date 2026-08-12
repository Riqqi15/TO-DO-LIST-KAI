import './bootstrap';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { toast } from 'vue-sonner';

router.on('httpException', (event) => {
    const status = event.detail.response?.status;
    if (status === 422) return;

    console.error('Server mengembalikan respons error:', event.detail.response);
    toast.error(`Permintaan gagal diproses server (${status ?? 'tanpa status'}).`);
});

router.on('networkError', (event) => {
    console.error('Permintaan gagal:', event.detail.error);
    toast.error('Permintaan tidak dapat diselesaikan. Periksa koneksi lalu coba lagi.');
});

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
