import {defineConfig} from 'vite';

import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    base: '/build/',
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    optimizeDeps: {
        include: ['alpinejs'],
    },
});
