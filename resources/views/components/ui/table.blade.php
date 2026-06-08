@props([
    'minWidth' => '640px',
])
{{--
    Responsive table shell. Put <thead>/<tbody> in the slot and rely on the
    .ui-table styling (token-driven, premium in both themes). Horizontal
    scroll kicks in below `minWidth`.
--}}
<div {{ $attributes->merge(['class' => 'ui-card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="ui-table" style="min-width: {{ $minWidth }}">
            {{ $slot }}
        </table>
    </div>
</div>
