@props([
    'href' => null,
    'action' => null,
    'method' => 'POST',
    'confirm' => null,
    'confirmTitle' => null,
    'confirmLabel' => null,
    'confirmVariant' => null,
    'variant' => 'default',
])
{{--
    A single item inside <x-ui.action-menu>. Three shapes:

      1. Link       — pass :href            → renders <a role="menuitem">
      2. POST form  — pass :action (+method)→ renders <form>@csrf[@method]<button>
                      optional :confirm / :confirmTitle / :confirmLabel /
                      :confirmVariant drive the shared <x-confirm-modal/>.
      3. Trigger    — pass neither          → renders a plain <button>; forward any
                      data-* (e.g. data-modal-target="#id") via attributes.

    variant="danger" tints the label red. Extra attributes merge onto the
    actionable element (button/anchor).
--}}
@php
    $itemClass = 'act-menu__item'.($variant === 'danger' ? ' act-menu__item--danger' : '');
@endphp
@if($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $itemClass]) }}>{{ $slot }}</a>
@elseif($action)
    <form method="POST" action="{{ $action }}" class="act-menu__form"
        @if($confirm) data-confirm="{{ $confirm }}" @endif
        @if($confirmTitle) data-confirm-title="{{ $confirmTitle }}" @endif
        @if($confirmLabel) data-confirm-label="{{ $confirmLabel }}" @endif
        @if($confirmVariant) data-confirm-variant="{{ $confirmVariant }}" @endif>
        @csrf
        @if(strtoupper($method) !== 'POST')@method($method)@endif
        <button type="submit" role="menuitem" {{ $attributes->merge(['class' => $itemClass]) }}>{{ $slot }}</button>
    </form>
@else
    <button type="button" role="menuitem" {{ $attributes->merge(['class' => $itemClass]) }}>{{ $slot }}</button>
@endif
