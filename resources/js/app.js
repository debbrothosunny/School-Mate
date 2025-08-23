// This file is the main entry point for your application's JavaScript.
// It sets up core libraries and frameworks like Vue, Inertia, and Echo.

import './bootstrap'; // <-- This line loads bootstrap and other dependencies
import '../css/app.css';

import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// ----------------------------------------------------------------------------
// Echo and Pusher-js Imports
// ----------------------------------------------------------------------------
// These imports are crucial for real-time functionality.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ----------------------------------------------------------------------------
// Initialize Pusher and Echo
// ----------------------------------------------------------------------------
// This block of code is what was missing.
// It creates the global 'window.Echo' object that your Vue components listen to.
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// ----------------------------------------------------------------------------
// Inertia App Initialization (Standard Inertia setup)
// ----------------------------------------------------------------------------
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
