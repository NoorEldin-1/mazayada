{{--
    Browse sidebar — a sticky panel holding the YouTube-style live search on top
    and the advanced, URL-driven filter form below. The form is a plain GET form
    with NO `page` field, so submitting it always lands on page 1 while the
    paginator links (withQueryString) carry these filters forward.
--}}
@php
    $status = (array) request('status', []);
    $assetClasses = (array) request('asset_class', []);
    $conditions = (array) request('condition', []);
    $crValue = request('requires_cr'); // null | '' | '1' | '0'
    $searchLabels = [
        'searching' => __('auctions.browse.search_searching'),
        'no_results' => __('auctions.browse.search_no_results'),
        'hint' => __('auctions.browse.search_hint'),
        'live' => __('auctions.live'),
    ];
@endphp

<aside class="br-side" id="brSide" data-br-side>

    {{-- ===== Live search ===== --}}
    <div class="br-card br-search" data-search
         data-endpoint="{{ route('auctions.search') }}"
         data-labels='@json($searchLabels)'>
        <label class="br-search-label" for="brSearchInput">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            {{ __('auctions.browse.search_title') }}
        </label>
        <div class="br-search-box">
            <input type="text" id="brSearchInput" class="br-search-input" data-search-input
                   autocomplete="off" role="combobox" aria-expanded="false"
                   aria-controls="brSearchResults" aria-autocomplete="list"
                   placeholder="{{ __('auctions.browse.search_placeholder') }}">
            <button type="button" class="br-search-clear" data-search-clear hidden aria-label="{{ __('common.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="br-search-results" id="brSearchResults" data-search-results role="listbox" hidden></div>
    </div>

    {{-- ===== Advanced filters ===== --}}
    <form method="GET" action="{{ route('auctions.index') }}" class="br-card br-filters" id="brFilters">
        <div class="br-filters-hd">
            <span class="br-filters-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                {{ __('auctions.browse.filters_title') }}
            </span>
            <a href="{{ route('auctions.index') }}" class="br-reset">{{ __('auctions.browse.reset') }}</a>
        </div>

        {{-- Sort --}}
        <div class="br-field">
            <label for="brSort">{{ __('auctions.browse.sort_label') }}</label>
            <select name="sort" id="brSort" class="select">
                <option value="" @selected(!request('sort'))>{{ __('auctions.browse.sort_newest') }}</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('auctions.browse.sort_price_asc') }}</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('auctions.browse.sort_price_desc') }}</option>
                <option value="most_bids" @selected(request('sort') === 'most_bids')>{{ __('auctions.browse.sort_most_bids') }}</option>
                <option value="ending_soon" @selected(request('sort') === 'ending_soon')>{{ __('auctions.browse.sort_ending_soon') }}</option>
            </select>
        </div>

        {{-- Type (single choice) --}}
        <div class="br-field">
            <label>{{ __('auctions.browse.filter_type') }}</label>
            <div class="br-chips" role="radiogroup">
                <label class="br-chip">
                    <input type="radio" name="type" value="" @checked(!request('type'))>
                    <span>{{ __('auctions.browse.type_all') }}</span>
                </label>
                @foreach(\App\Enums\AuctionType::cases() as $t)
                    <label class="br-chip">
                        <input type="radio" name="type" value="{{ $t->value }}" @checked(request('type') === $t->value)>
                        <span>{{ $t->label() }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Status (multi choice) --}}
        <div class="br-field">
            <label>{{ __('auctions.browse.filter_status') }}</label>
            <div class="br-chips">
                <label class="br-chip">
                    <input type="checkbox" name="status[]" value="upcoming" @checked(in_array('upcoming', $status, true))>
                    <span>{{ __('auctions.browse.status_upcoming') }}</span>
                </label>
                <label class="br-chip">
                    <input type="checkbox" name="status[]" value="live" @checked(in_array('live', $status, true))>
                    <span class="br-chip-live">{{ __('auctions.browse.status_live') }}</span>
                </label>
                <label class="br-chip">
                    <input type="checkbox" name="status[]" value="closed" @checked(in_array('closed', $status, true))>
                    <span>{{ __('auctions.browse.status_closed') }}</span>
                </label>
            </div>
        </div>

        {{-- Category --}}
        <div class="br-field">
            <label for="brCategory">{{ __('auctions.browse.filter_category') }}</label>
            <select name="category" id="brCategory" class="select">
                <option value="">{{ __('common.all') }}</option>
                @foreach($categories ?? [] as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Location (wilaya → commune) --}}
        <details class="br-group" open>
            <summary>
                {{ __('auctions.browse.section_location') }}
                <svg class="br-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <div class="br-group-body">
                <div class="br-field">
                    <label for="brWilaya">{{ __('auctions.browse.filter_wilaya') }}</label>
                    <select name="wilaya" id="brWilaya" class="select" data-wilaya
                            data-communes-url="{{ url('/api/v1/wilayas') }}"
                            data-placeholder="{{ __('auctions.browse.filter_commune_placeholder') }}"
                            data-all="{{ __('common.all') }}"
                            data-locale="{{ app()->getLocale() }}">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($wilayas ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('wilaya') == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="br-field">
                    <label for="brCommune">{{ __('auctions.browse.filter_commune') }}</label>
                    <select name="commune" id="brCommune" class="select" data-commune @disabled(!request('wilaya'))>
                        <option value="">{{ request('wilaya') ? __('common.all') : __('auctions.browse.filter_commune_placeholder') }}</option>
                        @foreach($communes ?? [] as $c)
                            <option value="{{ $c->id }}" @selected(request('commune') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </details>

        {{-- Price range --}}
        <details class="br-group" open>
            <summary>
                {{ __('auctions.browse.section_price') }}
                <svg class="br-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <div class="br-group-body">
                <div class="br-field">
                    <label>{{ __('auctions.browse.filter_price') }}</label>
                    <div class="br-range">
                        <input type="number" name="price_min" min="0" step="1000" inputmode="numeric"
                               value="{{ request('price_min') }}" class="input" placeholder="{{ __('auctions.browse.price_min') }}">
                        <span class="br-range-sep">—</span>
                        <input type="number" name="price_max" min="0" step="1000" inputmode="numeric"
                               value="{{ request('price_max') }}" class="input" placeholder="{{ __('auctions.browse.price_max') }}">
                    </div>
                </div>
            </div>
        </details>

        {{-- Asset details --}}
        <details class="br-group" @if($assetClasses || $conditions || request()->has('requires_cr')) open @endif>
            <summary>
                {{ __('auctions.browse.section_asset') }}
                <svg class="br-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <div class="br-group-body">
                <div class="br-field">
                    <label>{{ __('auctions.browse.filter_asset_class') }}</label>
                    <div class="br-checks">
                        @foreach(\App\Enums\AssetClass::cases() as $ac)
                            <label class="br-check">
                                <input type="checkbox" name="asset_class[]" value="{{ $ac->value }}" @checked(in_array($ac->value, $assetClasses, true))>
                                <span>{{ $ac->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="br-field">
                    <label>{{ __('auctions.browse.filter_condition') }}</label>
                    <div class="br-checks">
                        @foreach(\App\Enums\AssetCondition::cases() as $cond)
                            <label class="br-check">
                                <input type="checkbox" name="condition[]" value="{{ $cond->value }}" @checked(in_array($cond->value, $conditions, true))>
                                <span>{{ $cond->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="br-field">
                    <label>{{ __('auctions.browse.filter_requires_cr') }}</label>
                    <div class="br-chips">
                        <label class="br-chip">
                            <input type="radio" name="requires_cr" value="" @checked($crValue === null || $crValue === '')>
                            <span>{{ __('auctions.browse.cr_any') }}</span>
                        </label>
                        <label class="br-chip">
                            <input type="radio" name="requires_cr" value="1" @checked($crValue === '1')>
                            <span>{{ __('auctions.browse.cr_yes') }}</span>
                        </label>
                        <label class="br-chip">
                            <input type="radio" name="requires_cr" value="0" @checked($crValue === '0')>
                            <span>{{ __('auctions.browse.cr_no') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </details>

        <button type="submit" class="btn btn-primary br-apply">{{ __('auctions.browse.apply') }}</button>
    </form>
</aside>
