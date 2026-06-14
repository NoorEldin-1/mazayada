import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Live-auction bundle (Echo realtime + AJAX bidding). Loaded ONLY
                // from the public auction detail page (auctions/show.blade.php).
                'resources/js/auction.js',
                // Media gallery bundle (Swiper: hero + thumbs + fullscreen zoom).
                // Loaded ONLY from the public auction detail page; decoupled from
                // the realtime/bidding bundle so it runs on every auction page.
                'resources/js/gallery.js',
                // Dashboard-only bundle (admin + citizen). Loaded ONLY from the
                // dashboard layouts so public/auth pages are never affected.
                'resources/css/dashboard.css',
                'resources/js/dashboard.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
