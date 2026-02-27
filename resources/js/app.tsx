import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { LoadingProvider } from '@/props/LoadingContext';

const appName ='LOPI';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<LoadingProvider><App {...props} /></LoadingProvider>);
    },
    progress: {
        color: '#4B5563',
        includeCSS: true,
        showSpinner: true
    },
});

// This will set light / dark mode on load...
initializeTheme();
