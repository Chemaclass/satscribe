import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    base: '/build/',
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
        rollupOptions: {
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            output: {
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name === 'manifest.json') {
                        return 'manifest.json'; // Write to root
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build/.vite',
        }),
        tailwindcss(),
    ],
    optimizeDeps: {
        include: ['alpinejs'],
    },
});
