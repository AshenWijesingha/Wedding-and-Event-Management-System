import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    // Bind the dev server to 127.0.0.1 so the `public/hot` file points at the same
    // origin the app is served from (`php artisan serve` on 127.0.0.1). The Vite
    // default resolves to IPv6 `[::1]:5173`, which the browser treats as a different
    // origin from `127.0.0.1:8000` and fails to load the CSS/JS from in dev mode.
    server: {
        host: '127.0.0.1',
        strictPort: true,
        port: 5173,
    },
});
