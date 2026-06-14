{{-- Reverb (WebSocket) connection config injected from the SERVER so the
     committed, pre-built Vite bundle stays environment-independent. The values
     come from config/broadcasting.php (reverb connection options), which read
     REVERB_* from .env — so production .env (wss + real domain + 443) and local
     .env (ws + 127.0.0.1 + 8080) both work without rebuilding the JS.
     Loaded once per page that needs realtime (a classic inline <script>, so it
     runs before the deferred Vite module that constructs window.Echo). --}}
<script>
    window.__MAZAYADA_WS__ = {
        key: @json(config('broadcasting.connections.reverb.client.key')),
        host: @json(config('broadcasting.connections.reverb.client.host')),
        port: {{ (int) config('broadcasting.connections.reverb.client.port', 443) }},
        scheme: @json(config('broadcasting.connections.reverb.client.scheme', 'https')),
    };
</script>
