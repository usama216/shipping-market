import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast, { POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
import helpers from './helper';
createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .mixin(helpers)
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, {
                position: POSITION.TOP_RIGHT,
                timeout: 3000,
                closeOnClick: true,
            });

        const updateBodyClass = (componentName) => {
            const scrollablePages = [
                'Auth/Login',
                'Auth/Register',
                'Customers/Shipment/SuccessPage'
            ];
            if (scrollablePages.includes(componentName)) {
                document.body.classList.remove('overflow-hidden');
                document.body.classList.add('overflow-y-scroll');
            } else {
                document.body.classList.remove('overflow-y-scroll');
                document.body.classList.add('overflow-hidden');
            }
        };
        updateBodyClass(props.initialPage?.component);

        router.on('navigate', (event) => {
            updateBodyClass(event.detail.page.component);
        });

        return vueApp.mount(el);
    },
    progress: {
        color: '#9e1d22',
    },
});
