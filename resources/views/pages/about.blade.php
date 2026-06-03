@extends('layouts.app')
@section('title', __('pages.about.title_hl'))
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                {{ __('pages.about.eyebrow') }}
            </div>
            <h2>{{ __('pages.about.title_pre') }} <span class="hl">{{ __('pages.about.title_hl') }}</span></h2>
            <p>{{ __('common.platform_full') }}</p>
        </div>

        {{-- Mission --}}
        <div class="card card-pad" style="margin-bottom:32px;max-width:900px;margin-inline:auto">
            <h3 style="font-size:20px;font-weight:700;margin:0 0 12px;color:var(--primary)">{{ __('pages.about.mission_title') }}</h3>
            <p style="font-size:15px;color:var(--ink-2);line-height:1.8;margin:0">
                {{ __('pages.about.mission_text') }}
            </p>
        </div>

        {{-- 4 Pillars --}}
        <div class="fgrid" style="margin-bottom:48px">
            @foreach([
                ['title' => __('pages.about.pillar_transparency_title'), 'desc' => __('pages.about.pillar_transparency_desc'), 'color' => '#2D6A4F'],
                ['title' => __('pages.about.pillar_speed_title'), 'desc' => __('pages.about.pillar_speed_desc'), 'color' => '#3A86C7'],
                ['title' => __('pages.about.pillar_security_title'), 'desc' => __('pages.about.pillar_security_desc'), 'color' => '#D4A843'],
                ['title' => __('pages.about.pillar_fairness_title'), 'desc' => __('pages.about.pillar_fairness_desc'), 'color' => '#6B45B7'],
            ] as $pillar)
            <div class="fcard">
                <div class="ic" style="background:{{ $pillar['color'] }}15;color:{{ $pillar['color'] }}">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>{{ $pillar['title'] }}</h3>
                <p>{{ $pillar['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Government Entities --}}
        <div class="sect-head"><h2>{{ __('pages.about.gov_pre') }} <span class="hl">{{ __('pages.about.gov_hl') }}</span></h2></div>
        <div class="entities-grid" style="margin-bottom:48px">
            @foreach([
                ['code' => 'DGD', 'name' => __('home.entities.dgd'), 'desc' => __('pages.about.entity_dgd_desc'), 'color' => '#2D6A4F'],
                ['code' => 'DGDPE', 'name' => __('home.entities.dgdpe'), 'desc' => __('pages.about.entity_dgdpe_desc'), 'color' => '#3A86C7'],
                ['code' => 'APC', 'name' => __('home.entities.apc'), 'desc' => __('pages.about.entity_apc_desc'), 'color' => '#9A7008'],
                ['code' => 'HUI', 'name' => __('home.entities.hui'), 'desc' => __('pages.about.entity_hui_desc'), 'color' => '#6B45B7'],
                ['code' => 'DGI', 'name' => __('home.entities.dgi'), 'desc' => __('pages.about.entity_dgi_desc'), 'color' => '#B14641'],
            ] as $entity)
            <div class="ent-cell">
                <div class="ent-logo" style="background:{{ $entity['color'] }}">{{ $entity['code'] }}</div>
                <div class="ent-name">{{ $entity['name'] }}</div>
                <div class="ent-count" style="font-size:11px;color:var(--muted)">{{ $entity['desc'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Legal Framework --}}
        <div class="sect-head"><h2>{{ __('pages.about.legal_pre') }} <span class="hl">{{ __('pages.about.legal_hl') }}</span></h2></div>
        <div style="max-width:800px;margin:0 auto;display:grid;gap:12px">
            @foreach([
                __('pages.about.law_1'),
                __('pages.about.law_2'),
                __('pages.about.law_3'),
                __('pages.about.law_4'),
                __('pages.about.law_5'),
            ] as $law)
            <div class="card card-pad" style="display:flex;align-items:center;gap:12px">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(27,77,62,.08);color:var(--primary);display:grid;place-items:center;flex-shrink:0">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <span style="font-size:14px;font-weight:500">{{ $law }}</span>
            </div>
            @endforeach
        </div>

        {{-- Contact --}}
        <div style="text-align:center;margin-top:48px">
            <p style="color:var(--muted);font-size:14px;margin:0 0 8px">{{ __('pages.about.contact_prompt') }}</p>
            <p style="font-size:16px;margin:0"><strong>contact@mazayada.dz</strong> · <span class="num">+213 (0) 23 45 67 89</span></p>
        </div>
    </div>
</section>

@endsection
