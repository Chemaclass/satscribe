import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
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
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split Lucide and Alpine into separate chunks
                    lucide: ['lucide'],
                    alpine: ['alpinejs'],
                }
            }
        }
    }
});
