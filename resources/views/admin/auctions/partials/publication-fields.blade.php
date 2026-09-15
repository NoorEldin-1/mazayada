{{--
    Sector rule + publication rights (edits 11 · 13-17), shared by create/edit.
    Expects: $auction (nullable). The fee shown here is a live preview only —
    AdminAuctionController::applyPublication recomputes it server-side.
--}}
@php
    $a = $auction ?? null;
    $packages = \App\Models\PublicationPackage::where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
    $selectedPackage = old('publication_package_id', $a?->publication_package_id);
    $selectedPriority = old('publication_priority', $a?->publication_priority?->value ?? 'NORMAL');
    $feeLocked = $a?->publication_fee_paid_at !== null;
@endphp

<x-ui.card :title="__('publication.sec_title')" class="mb-6">
    <p style="font-size:0.85rem;color:var(--ink-muted);margin-bottom:1rem">{{ __('publication.sec_hint') }}</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:1rem">
        <div class="field">
            <label for="min_increment_percent">{{ __('admin.auctions.f_min_increment_percent') }}</label>
            <input type="number" id="min_increment_percent" name="min_increment_percent" class="input num" min="0" max="100" step="0.01"
                   value="{{ old('min_increment_percent', $a?->min_increment_percent !== null ? format_percent($a->min_increment_percent) : '') }}">
            <small style="color:var(--ink-muted)">{{ __('admin.auctions.min_increment_override_hint') }}</small>
            @error('min_increment_percent') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="publication_package_id">{{ __('publication.f_package') }} @if($packages->isNotEmpty())<span class="text-danger">*</span>@endif</label>
            <select id="publication_package_id" name="publication_package_id" class="select" @disabled($feeLocked) @if($packages->isNotEmpty()) required @endif>
                <option value="" data-price="0" data-priority-price="0">{{ $packages->isNotEmpty() ? __('publication.choose_package') : __('publication.no_package') }}</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}" data-price="{{ $package->price }}" data-priority-price="{{ $package->priority_price }}" @selected((string) $selectedPackage === (string) $package->id)>
                        {{ $package->name }} — {{ $package->display_area->label() }}
                    </option>
                @endforeach
            </select>
            @if($feeLocked)<input type="hidden" name="publication_package_id" value="{{ $a->publication_package_id }}">@endif
            @error('publication_package_id') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="publication_priority">{{ __('publication.f_priority') }}</label>
            <select id="publication_priority" name="publication_priority" class="select">
                @foreach(\App\Enums\PublicationPriority::cases() as $priority)
                    <option value="{{ $priority->value }}" @selected($selectedPriority === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select>
            @error('publication_priority') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </div>

    {{-- Live cost breakdown (edit 17) — shown before payment. --}}
    <div id="publication-cost" class="mt-4 p-3 rounded-xl border border-line" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:.75rem"
         data-currency="{{ __('common.currency') }}">
        <div><div class="text-xs text-muted">{{ __('publication.cost_package') }}</div><div class="font-semibold num" data-cost="package">—</div></div>
        <div><div class="text-xs text-muted">{{ __('publication.cost_priority') }}</div><div class="font-semibold num" data-cost="priority">—</div></div>
        <div><div class="text-xs text-muted">{{ __('publication.cost_total') }}</div><div class="font-bold text-primary num" data-cost="total">—</div></div>
    </div>
</x-ui.card>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var pkg = document.getElementById('publication_package_id');
    var prio = document.getElementById('publication_priority');
    var box = document.getElementById('publication-cost');
    if (!pkg || !prio || !box) return;
    var cur = box.getAttribute('data-currency') || '';
    function fmt(centimes) {
        return Math.round(centimes / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ' + cur;
    }
    function render() {
        var opt = pkg.options[pkg.selectedIndex];
        var base = parseInt(opt ? opt.getAttribute('data-price') : '0', 10) || 0;
        var extra = prio.value === 'PRIORITY' ? (parseInt(opt ? opt.getAttribute('data-priority-price') : '0', 10) || 0) : 0;
        box.querySelector('[data-cost="package"]').textContent = fmt(base);
        box.querySelector('[data-cost="priority"]').textContent = fmt(extra);
        box.querySelector('[data-cost="total"]').textContent = fmt(base + extra);
    }
    pkg.addEventListener('change', render);
    prio.addEventListener('change', render);
    render();
});
</script>
