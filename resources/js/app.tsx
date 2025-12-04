import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import i18n from "./i18n";


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const initialLocale = (props.initialPage.props?.locale as string) ?? 'vi';
        i18n.changeLanguage(initialLocale);

        const root = createRoot(el);

        root.render(
            <App {...props} />
        );
    },
    progress: {
        color: '#4B5563',
    },
});

