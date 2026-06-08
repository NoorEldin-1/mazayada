@props([
    'title' => null,
    'padding' => true,
])
{{--
    Generic surface card.
    - Pass `title` for a simple header, or use the `header` slot for custom
      header content, and the `actions` slot for trailing controls.
    - `padding` toggles the body padding (false for flush tables/lists).
--}}
<div {{ $attributes->merge(['class' => 'ui-card overflow-hidden']) }}>
    @isset($header)
        <div class="flex items-center gap-3 px-5 sm:px-6 py-4 border-b border-line">
            {{ $header }}
        </div>
    @elseif($title)
        <div class="flex items-center gap-3 px-5 sm:px-6 py-4 border-b border-line">
            <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
            @isset($actions)
                <div class="ms-auto flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding ? 'p-5 sm:p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
