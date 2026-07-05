{{--
    Advanced filter bar for the Financial Reports module.
    - Quick range presets (chips) that preserve every other active filter.
    - A GET form for custom date range + payment type/status + category /
      wilaya / entity + amount range + auction search.

    Expects: $filters (ReportFilters), $routeIndex (route name), $categories,
    and (platform admin only) $isPlatform, $wilayas, $entities.
--}}
@php
    $presetBase = $filters->toQueryWithoutRange();
    $isPlatform = $isPlatform ?? false;
    $wilayas = $wilayas ?? collect();
    $entities = $entities ?? collect();
@endphp

{{-- Quick range presets --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach(\App\Support\ReportFilters::PRESETS as $preset)
        <a href="{{ route($routeIndex, array_merge($presetBase, ['preset' => $preset])) }}"
           class="chip {{ $filters->preset === $preset ? 'chip-info' : 'chip-muted' }}">
            {{ __('reports.preset_'.$preset) }}
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route($routeIndex) }}" class="ui-card p-5 mb-5">
    {{-- Persist the quick preset across non-date filter edits. Typed custom
         dates always win over it (see ReportFilters::resolveRange). --}}
    @if(in_array($filters->preset, ['today','7d','30d','this_month','quarter','this_year'], true))
        <input type="hidden" name="preset" value="{{ $filters->preset }}">
    @endif

    <div class="flex items-center gap-2 mb-4">
        <svg class="size-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <h3 class="text-sm font-semibold text-ink">{{ __('reports.filters_title') }}</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_from') }}</label>
            <input type="date" name="from" class="input" value="{{ $filters->preset === 'custom' ? $filters->from?->format('Y-m-d') : '' }}">
        </div>
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_to') }}</label>
            <input type="date" name="to" class="input" value="{{ $filters->preset === 'custom' ? $filters->to?->format('Y-m-d') : '' }}">
        </div>
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_category') }}</label>
            <select name="category_id" class="select">
                <option value="">{{ __('reports.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters->categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        @if($isPlatform && $wilayas->isNotEmpty())
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_wilaya') }}</label>
            <select name="wilaya_id" class="select">
                <option value="">{{ __('reports.all_wilayas') }}</option>
                @foreach($wilayas as $wilaya)
                    <option value="{{ $wilaya->id }}" @selected($filters->wilayaId === $wilaya->id)>{{ $wilaya->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if($isPlatform && $entities->isNotEmpty())
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_entity') }}</label>
            <select name="entity_id" class="select">
                <option value="">{{ __('reports.all_entities') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" @selected($filters->entityId === $entity->id)>{{ $entity->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_min_amount') }}</label>
            <input type="number" name="min" min="0" step="1" inputmode="numeric" class="input" value="{{ $filters->minCentimes !== null ? intdiv($filters->minCentimes, 100) : '' }}">
        </div>
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_max_amount') }}</label>
            <input type="number" name="max" min="0" step="1" inputmode="numeric" class="input" value="{{ $filters->maxCentimes !== null ? intdiv($filters->maxCentimes, 100) : '' }}">
        </div>
        <div class="sm:col-span-2 lg:col-span-2">
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('reports.f_search') }}</label>
            <input type="text" name="search" class="input" value="{{ $filters->search }}" placeholder="{{ __('reports.f_search_placeholder') }}">
        </div>
    </div>

    {{-- Payment type (multi) --}}
    <div class="mt-4">
        <div class="text-[13px] font-medium text-muted mb-2">{{ __('reports.f_type') }}</div>
        <div class="flex flex-wrap gap-2">
            @foreach(\App\Enums\PaymentType::cases() as $type)
                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-line bg-bg text-sm cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary">
                    <input type="checkbox" class="accent-current" name="type[]" value="{{ $type->value }}" @checked(in_array($type->value, $filters->types, true))>
                    <span>{{ $type->label() }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Status (multi) --}}
    <div class="mt-3">
        <div class="text-[13px] font-medium text-muted mb-2">{{ __('reports.f_status') }}</div>
        <div class="flex flex-wrap gap-2">
            @foreach(\App\Enums\PaymentStatus::cases() as $status)
                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-line bg-bg text-sm cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary">
                    <input type="checkbox" class="accent-current" name="status[]" value="{{ $status->value }}" @checked(in_array($status->value, $filters->statuses, true))>
                    <span>{{ $status->label() }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mt-5 pt-4 border-t border-line">
        <x-ui.btn type="submit" variant="primary" size="sm">{{ __('reports.apply') }}</x-ui.btn>
        @if($filters->isActive())
            <x-ui.btn :href="route($routeIndex)" variant="ghost" size="sm">{{ __('reports.reset') }}</x-ui.btn>
        @endif
        <span class="text-xs text-muted ms-auto">{{ __('reports.date_note') }}</span>
    </div>
</form>
