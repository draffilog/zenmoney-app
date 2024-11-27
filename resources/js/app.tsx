import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (import.meta.env.VITE_DEBUG) {
    console.log('App initialization started');
    console.log('Available pages:', import.meta.glob('./pages/**/*.tsx'));
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        if (import.meta.env.VITE_DEBUG) {
            console.log('Resolving page:', name);
        }
        const page = await resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));
        if (import.meta.env.VITE_DEBUG) {
            console.log('Resolved page:', page);
        }
        return page;
    },
    setup({ el, App, props }) {
        if (import.meta.env.VITE_DEBUG) {
            console.log('Setting up app with props:', props);
        }
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
