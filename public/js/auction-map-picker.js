/**
 * Admin auction location picker (Leaflet + OpenStreetMap).
 *
 * Self-hosted, no API key. Lets an admin pick the asset's exact point by
 * searching an address (Nominatim), dragging the pin, or clicking the map.
 * The chosen point fills the hidden #latitude / #longitude inputs and the
 * #asset_location text (reverse-geocoded). Loaded only from the auction
 * create/edit forms; it no-ops on every other page.
 *
 * Nominatim usage policy: low-volume admin use only, <=1 req/sec — the search
 * box is debounced. For high-volume production geocoding, swap to a dedicated
 * provider (the fetch URLs are the only thing that changes).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var mapEl = document.getElementById('asset-map');
        if (!mapEl || typeof L === 'undefined') return;

        var root = mapEl.closest('.map-picker') || document;
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var addrInput = document.getElementById('asset_location');
        var searchInput = document.getElementById('map-search');
        var resultsEl = document.getElementById('map-search-results');
        var clearBtn = document.getElementById('map-clear');
        var geoBtn = document.getElementById('map-geolocate');
        var locale = root.getAttribute && root.getAttribute('data-locale') || 'ar';

        // Algeria centroid — the default view when no point is set yet.
        var ALGERIA = [28.0339, 1.6596];
        var hasPoint = latInput.value !== '' && lngInput.value !== '';
        var start = hasPoint ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : ALGERIA;

        L.Icon.Default.imagePath = '/vendor/leaflet/images/';

        var map = L.map(mapEl, { scrollWheelZoom: true }).setView(start, hasPoint ? 14 : 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        // A teardrop SVG pin (matches the public asset-location card aesthetic).
        var pinIcon = L.divIcon({
            className: 'mz-pin',
            html: '<svg viewBox="0 0 24 24" width="34" height="34" fill="#E8830C" stroke="#fff" stroke-width="1.5"><path d="M12 2C7.6 2 4 5.6 4 10c0 5.4 8 12 8 12s8-6.6 8-12c0-4.4-3.6-8-8-8z"/><circle cx="12" cy="10" r="3" fill="#fff" stroke="none"/></svg>',
            iconSize: [34, 34],
            iconAnchor: [17, 32],
        });

        var marker = null;

        // Leaflet measures the container on init; if it was laid out after init
        // (cards, tabs) the tiles render grey until size is recomputed.
        setTimeout(function () { map.invalidateSize(); }, 200);

        function setInputs(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
        }

        function placeMarker(lat, lng, fly) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    var p = marker.getLatLng();
                    setInputs(p.lat, p.lng);
                    reverseGeocode(p.lat, p.lng);
                });
            }
            setInputs(lat, lng);
            if (fly) map.setView([lat, lng], Math.max(map.getZoom(), 14));
        }

        if (hasPoint) placeMarker(start[0], start[1], false);

        // --- Click to drop / move the pin -------------------------------------
        map.on('click', function (e) {
            placeMarker(e.latlng.lat, e.latlng.lng, false);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // --- Reverse geocode: fill the address text from the dropped point ----
        function reverseGeocode(lat, lng) {
            if (!addrInput) return;
            fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat +
                  '&lon=' + lng + '&accept-language=' + encodeURIComponent(locale))
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.display_name) addrInput.value = data.display_name;
                })
                .catch(function () { /* network error — keep whatever text exists */ });
        }

        // --- Address search (Nominatim), debounced ----------------------------
        function clearResults() {
            if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; }
        }

        function runSearch(q) {
            if (!q || q.length < 3) { clearResults(); return; }
            fetch('https://nominatim.openstreetmap.org/search?format=json&countrycodes=dz&limit=5' +
                  '&accept-language=' + encodeURIComponent(locale) + '&q=' + encodeURIComponent(q))
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (list) {
                    if (!resultsEl) return;
                    resultsEl.innerHTML = '';
                    if (!list.length) { resultsEl.style.display = 'none'; return; }
                    list.forEach(function (item) {
                        var li = document.createElement('li');
                        li.textContent = item.display_name;
                        li.addEventListener('click', function () {
                            var lat = parseFloat(item.lat), lng = parseFloat(item.lon);
                            placeMarker(lat, lng, true);
                            if (addrInput) addrInput.value = item.display_name;
                            clearResults();
                            searchInput.value = item.display_name;
                        });
                        resultsEl.appendChild(li);
                    });
                    resultsEl.style.display = 'block';
                })
                .catch(function () { clearResults(); });
        }

        if (searchInput) {
            var timer = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                var q = searchInput.value;
                timer = setTimeout(function () { runSearch(q); }, 500);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); runSearch(searchInput.value); }
            });
            // Dismiss the results when clicking elsewhere.
            document.addEventListener('click', function (e) {
                if (resultsEl && !resultsEl.contains(e.target) && e.target !== searchInput) clearResults();
            });
        }

        // --- Forward geocode the typed/pasted asset address -------------------
        // Strip a leading Google Plus Code (e.g. "QW55+CG7, ") that Nominatim
        // cannot resolve, leaving the street part.
        function stripPlusCode(s) {
            var out = s.replace(/^\s*[A-Z0-9]{4,}\+[A-Z0-9]{2,}[\s,]*/i, '').trim();
            return out || s;
        }

        function forwardGeocode(q) {
            if (!q || q.length < 3) return;
            fetch('https://nominatim.openstreetmap.org/search?format=json&countrycodes=dz&limit=1' +
                  '&accept-language=' + encodeURIComponent(locale) + '&q=' + encodeURIComponent(stripPlusCode(q)))
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (list) {
                    if (list && list.length) {
                        placeMarker(parseFloat(list[0].lat), parseFloat(list[0].lon), true);
                    }
                })
                .catch(function () { /* network error — coordinates stay empty */ });
        }

        // When the admin types/pastes an address in #asset_location (not the map
        // search), drop a pin for it so the public map renders. addrDirty is set
        // only by real user input — reverse-geocode writes don't re-trigger it.
        if (addrInput) {
            var addrDirty = false;
            addrInput.addEventListener('input', function () { addrDirty = true; });
            var geocodeAddr = function () {
                if (addrDirty && addrInput.value.trim()) {
                    addrDirty = false;
                    forwardGeocode(addrInput.value.trim());
                }
            };
            addrInput.addEventListener('blur', geocodeAddr);
            addrInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); geocodeAddr(); }
            });
        }

        // --- Use my current location -----------------------------------------
        if (geoBtn && navigator.geolocation) {
            geoBtn.addEventListener('click', function () {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    var lat = pos.coords.latitude, lng = pos.coords.longitude;
                    placeMarker(lat, lng, true);
                    reverseGeocode(lat, lng);
                });
            });
        } else if (geoBtn) {
            geoBtn.style.display = 'none';
        }

        // --- Clear the chosen point ------------------------------------------
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (marker) { map.removeLayer(marker); marker = null; }
                latInput.value = '';
                lngInput.value = '';
                if (searchInput) searchInput.value = '';
                clearResults();
                map.setView(ALGERIA, 5);
            });
        }
    });
})();
