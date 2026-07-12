@extends('layouts.app')
@section('title', __('nav.appeals_system'))
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        {{-- Hero --}}
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                {{ __('appeals_guide.eyebrow') }}
            </div>
            <h2>{{ __('appeals_guide.title_pre') }} <span class="hl">{{ __('appeals_guide.title_hl') }}</span> {{ __('appeals_guide.title_post') }}</h2>
            <p>{{ __('appeals_guide.subtitle') }}</p>
        </div>

        {{-- Intro + Eligibility --}}
        <div style="max-width:900px;margin:0 auto 56px;display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))">
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('appeals_guide.intro_title') }}</h4>
                <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">{{ __('appeals_guide.intro_body') }}</p>
            </div>
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('appeals_guide.eligibility_title') }}</h4>
                <ul style="font-size:14px;color:var(--muted);line-height:1.9;margin:0;padding-inline-start:18px">
                    <li>{{ __('appeals_guide.eligibility_1') }}</li>
                    <li>{{ __('appeals_guide.eligibility_2') }}</li>
                    <li>{{ __('appeals_guide.eligibility_3') }}</li>
                </ul>
            </div>
        </div>

        {{-- Steps --}}
        <div class="sect-head">
            <h2>{{ __('appeals_guide.steps_title_pre') }} <span class="hl">{{ __('appeals_guide.steps_title_hl') }}</span></h2>
        </div>
        <div class="steps" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:32px;margin-bottom:64px">
            @foreach([
                ['n' => '1', 'title' => __('appeals_guide.step_1_title'), 'desc' => __('appeals_guide.step_1_desc')],
                ['n' => '2', 'title' => __('appeals_guide.step_2_title'), 'desc' => __('appeals_guide.step_2_desc'), 'alt' => true],
                ['n' => '3', 'title' => __('appeals_guide.step_3_title'), 'desc' => __('appeals_guide.step_3_desc')],
                ['n' => '4', 'title' => __('appeals_guide.step_4_title'), 'desc' => __('appeals_guide.step_4_desc'), 'alt' => true],
                ['n' => '5', 'title' => __('appeals_guide.step_5_title'), 'desc' => __('appeals_guide.step_5_desc')],
                ['n' => '6', 'title' => __('appeals_guide.step_6_title'), 'desc' => __('appeals_guide.step_6_desc'), 'alt' => true],
            ] as $step)
            <div class="step {{ ($step['alt'] ?? false) ? 'alt' : '' }}" style="position:relative">
                <div class="n">{{ $step['n'] }}</div>
                <h4>{{ $step['title'] }}</h4>
                <p>{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Window / conditions --}}
        <div style="max-width:900px;margin:0 auto 56px;display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))">
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('appeals_guide.window_title') }}</h4>
                <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">{{ __('appeals_guide.window_days_body', ['days' => config('mazayada.appeals.window_days')]) }}</p>
            </div>
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('appeals_guide.window_where_title') }}</h4>
                <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">{{ __('appeals_guide.window_where_body') }}</p>
            </div>
        </div>

        {{-- Inspection --}}
        <div class="sect-head">
            <h2>{{ __('appeals_guide.inspection_title') }}</h2>
            <p>{{ __('appeals_guide.inspection_body') }}</p>
        </div>
        <div style="max-width:900px;margin:0 auto 64px;display:grid;gap:14px">
            @foreach([
                __('appeals_guide.inspection_1'),
                __('appeals_guide.inspection_2'),
                __('appeals_guide.inspection_3'),
            ] as $item)
            <div class="card card-pad" style="display:flex;gap:12px;align-items:flex-start">
                <svg width="20" height="20" style="flex-shrink:0;color:var(--brand,#166534)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                <span style="font-size:14px;color:var(--ink);line-height:1.7">{{ $item }}</span>
            </div>
            @endforeach
        </div>

        {{-- FAQ --}}
        <div class="sect-head">
            <h2>{{ __('appeals_guide.faq_title_pre') }} <span class="hl">{{ __('appeals_guide.faq_title_hl') }}</span></h2>
        </div>
        <div style="max-width:800px;margin:0 auto 64px;display:grid;gap:14px">
            @foreach([
                ['q' => __('appeals_guide.faq_1_q'), 'a' => __('appeals_guide.faq_1_a')],
                ['q' => __('appeals_guide.faq_2_q'), 'a' => __('appeals_guide.faq_2_a')],
                ['q' => __('appeals_guide.faq_3_q'), 'a' => __('appeals_guide.faq_3_a')],
                ['q' => __('appeals_guide.faq_4_q'), 'a' => __('appeals_guide.faq_4_a')],
                ['q' => __('appeals_guide.faq_5_q'), 'a' => __('appeals_guide.faq_5_a')],
            ] as $faq)
            <div class="card card-pad">
                <strong style="font-size:15px;color:var(--ink)">{{ $faq['q'] }}</strong>
                <p style="font-size:13px;color:var(--muted);margin:8px 0 0;line-height:1.7">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="card card-pad" style="max-width:800px;margin:0 auto;text-align:center">
            <h4 style="margin:0 0 8px;color:var(--ink)">{{ __('appeals_guide.cta_title') }}</h4>
            <p style="font-size:14px;color:var(--muted);margin:0 0 18px;line-height:1.7">{{ __('appeals_guide.cta_body') }}</p>
            <a href="{{ route('auctions.index') }}" class="btn btn-primary btn-lg">{{ __('appeals_guide.cta_btn') }}</a>
        </div>
    </div>
</section>

@endsection
