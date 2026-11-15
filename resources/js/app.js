import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'FreshNest Cleaning';

/** Layout is chosen by page folder so individual pages never need to import one. */
function layoutFor(name) {
    if (name === 'Admin/Bookings/Print') return null; // bare printable page
    if (name.startsWith('Admin/')) return AdminLayout;
    if (name.startsWith('Staff/')) return StaffLayout;
    if (name.startsWith('Customer/')) return CustomerLayout;

    return PublicLayout;
}

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Page not found: ${name}`);
        page.default.layout ??= layoutFor(name);

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .mount(el);
    },
    progress: { color: '#2F855A' },
});
