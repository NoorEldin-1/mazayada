{{--
    Advanced filter bar for the Document Library.
    - Quick range presets (chips) over the document's issue date.
    - A GET form for custom range + document type (multi) + category / wilaya /
      entity + sort + full-text search. The active `view` is preserved throughout.

    Expects: $filters (DocumentFilters), $view, $categories, $wilayas, $entities.
--}}
@php
    use App\Enums\DocumentType;
    $presetBase = array_merge($filters->toQueryWithoutRange(), ['view' => $view]);
    $libTypes = array_filter(DocumentType::cases(), fn ($t) => $t !== DocumentType::AUCTION_REPORT);
@endphp

{{-- Quick range presets --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach(\App\Support\DocumentFilters::PRESETS as $preset)
        <a href="{{ route('citizen.documents', array_merge($presetBase, ['preset' => $preset])) }}"
           class="chip {{ $filters->preset === $preset ? 'chip-info' : 'chip-muted' }}">
            {{ $preset === 'all' ? __('documents.lib_range_all') : __('documents.lib_preset_'.$preset) }}
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('citizen.documents') }}" class="ui-card p-5 mb-5">
    <input type="hidden" name="view" value="{{ $view }}">
    @if(in_array($filters->preset, ['today','7d','30d','this_month','this_year'], true))
        <input type="hidden" name="preset" value="{{ $filters->preset }}">
    @endif

    <div class="flex items-center gap-2 mb-4">
        <svg class="size-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <h3 class="text-sm font-semibold text-ink">{{ __('documents.lib_filters_title') }}</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_from') }}</label>
            <input type="date" name="from" class="input" value="{{ $filters->preset === 'custom' ? $filters->from?->format('Y-m-d') : '' }}">
        </div>
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_to') }}</label>
            <input type="date" name="to" class="input" value="{{ $filters->preset === 'custom' ? $filters->to?->format('Y-m-d') : '' }}">
        </div>

        @if($categories->isNotEmpty())
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_category') }}</label>
            <select name="category_id" class="select">
                <option value="">{{ __('documents.lib_all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters->categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if($wilayas->isNotEmpty())
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_wilaya') }}</label>
            <select name="wilaya_id" class="select">
                <option value="">{{ __('documents.lib_all_wilayas') }}</option>
                @foreach($wilayas as $wilaya)
                    <option value="{{ $wilaya->id }}" @selected($filters->wilayaId === $wilaya->id)>{{ $wilaya->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if($entities->isNotEmpty())
        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_entity') }}</label>
            <select name="entity_id" class="select">
                <option value="">{{ __('documents.lib_all_entities') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" @selected($filters->entityId === $entity->id)>{{ $entity->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_sort') }}</label>
            <select name="sort" class="select">
                @foreach(\App\Support\DocumentFilters::SORTS as $sort)
                    <option value="{{ $sort }}" @selected($filters->sort === $sort)>{{ __('documents.lib_sort_'.$sort) }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-2">
            <label class="block text-[13px] font-medium text-muted mb-1.5">{{ __('documents.lib_f_search') }}</label>
            <input type="text" name="search" class="input" value="{{ $filters->search }}" placeholder="{{ __('documents.lib_f_search_placeholder') }}">
        </div>
    </div>

    {{-- Document type (multi) --}}
    <div class="mt-4">
        <div class="text-[13px] font-medium text-muted mb-2">{{ __('documents.lib_f_type') }}</div>
        <div class="flex flex-wrap gap-2">
            @foreach($libTypes as $type)
                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-line bg-bg text-sm cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary">
                    <input type="checkbox" class="accent-current" name="type[]" value="{{ $type->value }}" @checked(in_array($type->value, $filters->types, true))>
                    <span>{{ $type->label() }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-5 pt-4 border-t border-line">
        <div class="flex items-stretch gap-3">
            <x-ui.btn type="submit" variant="primary" size="lg" class="basis-4/5 grow text-base">{{ __('documents.lib_apply') }}</x-ui.btn>
            @if($filters->isActive())
                <x-ui.btn :href="route('citizen.documents', ['view' => $view])" variant="ghost" size="lg" class="basis-1/5 grow text-base">{{ __('documents.lib_reset') }}</x-ui.btn>
            @endif
        </div>
    </div>
</form>
