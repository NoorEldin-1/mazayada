{{--
    Shared legal / policy page shell.

    Fully data-driven: every string comes from lang/<locale>/legal.php, so the
    four legal pages (terms, privacy, framework, notices) share one consistent,
    RTL/LTR-safe layout and a new page is just one <x-legal.page /> call.

    Props:
      base    — the lang base key for this page, e.g. "legal.terms"
      current — this page's short key ("terms"), excluded from the related list

    Usage:  <x-legal.page base="legal.terms" current="terms" />
--}}
@props(['base', 'current'])

@php
    $t = __($base);
    $sections = is_array($t) ? ($t['sections'] ?? []) : [];

    // All legal pages, in footer order — used for the cross-navigation block.
    $allPages = ['framework', 'privacy', 'terms', 'notices'];
@endphp

<section class="legal-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="legal-crumbs" aria-label="breadcrumb">
            <a href="{{ route('home') }}">{{ __('legal.ui.home') }}</a>
            <span class="sep" aria-hidden="true">/</span>
            <span class="cur">{{ $t['title'] }}</span>
        </nav>

        {{-- Header --}}
        <header class="legal-head">
            <span class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                {{ $t['eyebrow'] }}
            </span>
            <h1>{{ $t['title'] }}</h1>
            @if(!empty($t['intro']))
                <p class="legal-intro">{{ $t['intro'] }}</p>
            @endif
            <div class="legal-updated">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('legal.ui.updated', ['date' => __('legal.ui.updated_date')]) }}
            </div>
        </header>

        {{-- Document body --}}
        <article class="legal-body card">
            @foreach($sections as $i => $section)
                <section class="legal-section">
                    <h2><span class="legal-num num">{{ $i + 1 }}</span>{{ $section['title'] }}</h2>
                    @if(!empty($section['body']))
                        <p>{{ $section['body'] }}</p>
                    @endif
                    @if(!empty($section['points']))
                        <ul>
                            @foreach($section['points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <p class="legal-disclaimer">{{ __('legal.ui.disclaimer') }}</p>
        </article>

        {{-- Related legal pages --}}
        <aside class="legal-related">
            <h3>{{ __('legal.ui.related_title') }}</h3>
            <div class="legal-related-grid">
                @foreach($allPages as $key)
                    @continue($key === $current)
                    <a href="{{ route('legal.'.$key) }}" class="legal-related-card">
                        <span>{{ __('legal.'.$key.'.title') }}</span>
                        <svg class="arr" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                @endforeach
            </div>
        </aside>

    </div>
</section>
