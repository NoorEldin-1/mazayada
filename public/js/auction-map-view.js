/**
 * Public asset-location map (Leaflet + OpenStreetMap), read-only.
 *
 * Renders a static pin for every `.asset-map[data-lat][data-lng]` on the page
 * (the auction detail sidebar). Self-hosted, no API key. Scroll-wheel zoom is
 * disabled so the map never hijacks page scrolling. No-ops when absent.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') return;

        L.Icon.Default.imagePath = '/vendor/leaflet/images/';

        var pinIcon = L.divIcon({
            className: 'mz-pin',
            html: '<svg viewBox="0 0 24 24" width="34" height="34" fill="#E8830C" stroke="#fff" stroke-width="1.5"><path d="M12 2C7.6 2 4 5.6 4 10c0 5.4 8 12 8 12s8-6.6 8-12c0-4.4-3.6-8-8-8z"/><circle cx="12" cy="10" r="3" fill="#fff" stroke="none"/></svg>',
            iconSize: [34, 34],
            iconAnchor: [17, 32],
        });

        document.querySelectorAll('.asset-map[data-lat][data-lng]').forEach(function (el) {
            var lat = parseFloat(el.getAttribute('data-lat'));
            var lng = parseFloat(el.getAttribute('data-lng'));
            if (isNaN(lat) || isNaN(lng)) return;

            var map = L.map(el, {
                scrollWheelZoom: false,
                zoomControl: true,
                dragging: true,
            }).setView([lat, lng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            L.marker([lat, lng], { icon: pinIcon, interactive: false, keyboard: false }).addTo(map);

            // The card may be laid out after init (sidebar) — recompute tiles.
            setTimeout(function () { map.invalidateSize(); }, 200);
        });
    });
})();
