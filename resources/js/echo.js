import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// The Reverb connection config is injected at runtime by the server
// (see resources/views/partials/ws-config.blade.php) rather than read from
// import.meta.env. This keeps the COMMITTED, pre-built bundle environment
// independent: the same public/build artifact works locally (ws / 127.0.0.1)
// and in production (wss / the real domain) without rebuilding with prod
// secrets — which matters because we build locally and ship public/build.
const cfg = window.__MAZAYADA_WS__ ?? {};

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: cfg.key,
    wsHost: cfg.host,
    wsPort: cfg.port ?? 80,
    wssPort: cfg.port ?? 443,
    forceTLS: (cfg.scheme ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
