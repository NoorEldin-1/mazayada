@props([
    'title' => null,
    'subtitle' => null,
])
{{-- Page section header with an optional trailing `actions` slot. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 mb-5']) }}>
    <div class="min-w-0">
        @if ($title)
            <h2 class="text-lg sm:text-xl font-bold text-ink">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="text-sm text-muted mt-0.5">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    @isset($actions)
        <div class="flex items-center gap-2.5 shrink-0">{{ $actions }}</div>
    @endisset
</div>
