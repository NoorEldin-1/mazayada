{{--
    Language switcher.

    Renders one link per supported locale (from config/locales.php) pointing at
    the lang.switch route, which stores the choice in the session and — for
    authenticated users — on their account. Works for guests and every role.

    Usage:  <x-lang-switcher />
--}}
@php($current = app()->getLocale())
<div class="lang-switch" role="group" aria-label="{{ __('nav.menu') }}">
    @foreach (config('locales.supported', ['ar']) as $code)
        <a
            href="{{ route('lang.switch', $code) }}"
            class="{{ $code === $current ? 'on' : '' }}"
            style="text-decoration:none"
            hreflang="{{ $code }}"
            lang="{{ $code }}"
            @if($code === $current) aria-current="true" @endif
        >{{ config("locales.meta.{$code}.label", strtoupper($code)) }}</a>
    @endforeach
</div>
