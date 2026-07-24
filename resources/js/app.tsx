import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const initialLocale =
            (props.initialPage.props?.locale as string) ?? 'vi';
        i18n.changeLanguage(initialLocale);

        // Sync locale on every Inertia navigation
        router.on('navigate', (event) => {
            const newLocale =
                (event.detail.page.props?.locale as string) ?? 'vi';
            if (i18n.language !== newLocale) {
                i18n.changeLanguage(newLocale);
            }
        });

        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
