@props(['lat' => null, 'lng' => null])

{{-- Self-hosted Leaflet assets, injected once per page. --}}
@once
    @push('styles')
        <link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
    @endpush
    @push('scripts')
        <script src="/vendor/leaflet/leaflet.js"></script>
        <script src="/js/auction-map-picker.js?v={{ filemtime(public_path('js/auction-map-picker.js')) }}"></script>
    @endpush
@endonce

{{-- Address search + draggable pin. Fills the hidden latitude/longitude and the
     (reverse-geocoded) #asset_location text input that lives in the grid above. --}}
<div class="map-picker field" data-locale="{{ app()->getLocale() }}" style="margin-top:1rem">
    <label>{{ __('admin.auctions.f_map_location') }}</label>

    <div class="map-picker-search">
        <input type="text" id="map-search" class="input" autocomplete="off"
               placeholder="{{ __('admin.auctions.f_map_search') }}">
        <button type="button" id="map-geolocate" class="map-picker-btn"
                title="{{ __('admin.auctions.f_map_geolocate') }}" aria-label="{{ __('admin.auctions.f_map_geolocate') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
        </button>
        <button type="button" id="map-clear" class="map-picker-btn"
                title="{{ __('admin.auctions.f_clear_location') }}" aria-label="{{ __('admin.auctions.f_clear_location') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <ul id="map-search-results" class="map-search-results"></ul>
    </div>

    <div id="asset-map" class="asset-map asset-map--picker"></div>
    <small class="map-picker-hint">{{ __('admin.auctions.f_map_hint') }}</small>

    <input type="hidden" id="latitude" name="latitude" value="{{ $lat }}">
    <input type="hidden" id="longitude" name="longitude" value="{{ $lng }}">
    @error('latitude') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
    @error('longitude') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
</div>
