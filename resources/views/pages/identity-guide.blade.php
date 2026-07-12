@extends('layouts.app')
@section('title', __('nav.identity_verification'))
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        {{-- Hero --}}
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                {{ __('identity_guide.eyebrow') }}
            </div>
            <h2>{{ __('identity_guide.title_pre') }} <span class="hl">{{ __('identity_guide.title_hl') }}</span> {{ __('identity_guide.title_post') }}</h2>
            <p>{{ __('identity_guide.subtitle') }}</p>
        </div>

        {{-- Intro + Requirements --}}
        <div style="max-width:900px;margin:0 auto 56px;display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))">
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('identity_guide.intro_title') }}</h4>
                <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">{{ __('identity_guide.intro_body') }}</p>
            </div>
            <div class="card card-pad">
                <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('identity_guide.requirements_title') }}</h4>
                <ul style="font-size:14px;color:var(--muted);line-height:1.9;margin:0 0 10px;padding-inline-start:18px">
                    <li>{{ __('identity_guide.req_1') }}</li>
                    <li>{{ __('identity_guide.req_2') }}</li>
                    <li>{{ __('identity_guide.req_3') }}</li>
                </ul>
                <p style="font-size:12px;color:var(--muted);margin:0;line-height:1.6">{{ __('identity_guide.req_note') }}</p>
            </div>
        </div>

        {{-- Steps --}}
        <div class="sect-head">
            <h2>{{ __('identity_guide.steps_title_pre') }} <span class="hl">{{ __('identity_guide.steps_title_hl') }}</span></h2>
        </div>
        <div class="steps" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:32px;margin-bottom:64px">
            @foreach([
                ['n' => '1', 'title' => __('identity_guide.step_1_title'), 'desc' => __('identity_guide.step_1_desc')],
                ['n' => '2', 'title' => __('identity_guide.step_2_title'), 'desc' => __('identity_guide.step_2_desc'), 'alt' => true],
                ['n' => '3', 'title' => __('identity_guide.step_3_title'), 'desc' => __('identity_guide.step_3_desc')],
                ['n' => '4', 'title' => __('identity_guide.step_4_title'), 'desc' => __('identity_guide.step_4_desc'), 'alt' => true],
                ['n' => '5', 'title' => __('identity_guide.step_5_title'), 'desc' => __('identity_guide.step_5_desc')],
                ['n' => '6', 'title' => __('identity_guide.step_6_title'), 'desc' => __('identity_guide.step_6_desc'), 'alt' => true],
            ] as $step)
            <div class="step {{ ($step['alt'] ?? false) ? 'alt' : '' }}" style="position:relative">
                <div class="n">{{ $step['n'] }}</div>
                <h4>{{ $step['title'] }}</h4>
                <p>{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Statuses --}}
        <div class="sect-head">
            <h2>{{ __('identity_guide.status_title') }}</h2>
        </div>
        <div style="max-width:900px;margin:0 auto 40px;display:grid;gap:14px">
            @foreach([
                __('identity_guide.status_pending'),
                __('identity_guide.status_review'),
                __('identity_guide.status_complete'),
                __('identity_guide.status_rejected'),
            ] as $item)
            <div class="card card-pad" style="display:flex;gap:12px;align-items:flex-start">
                <svg width="20" height="20" style="flex-shrink:0;color:var(--brand,#166534)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span style="font-size:14px;color:var(--ink);line-height:1.7">{{ $item }}</span>
            </div>
            @endforeach
        </div>

        {{-- Suspension notice --}}
        <div class="card card-pad" style="max-width:900px;margin:0 auto 64px">
            <h4 style="margin:0 0 10px;color:var(--ink)">{{ __('identity_guide.suspension_title') }}</h4>
            <p style="font-size:14px;color:var(--muted);line-height:1.8;margin:0">{{ __('identity_guide.suspension_body', ['days' => config('mazayada.kyc.pending_grace_days')]) }}</p>
        </div>

        {{-- FAQ --}}
        <div class="sect-head">
            <h2>{{ __('identity_guide.faq_title_pre') }} <span class="hl">{{ __('identity_guide.faq_title_hl') }}</span></h2>
        </div>
        <div style="max-width:800px;margin:0 auto 64px;display:grid;gap:14px">
            @foreach([
                ['q' => __('identity_guide.faq_1_q'), 'a' => __('identity_guide.faq_1_a')],
                ['q' => __('identity_guide.faq_2_q'), 'a' => __('identity_guide.faq_2_a')],
                ['q' => __('identity_guide.faq_3_q'), 'a' => __('identity_guide.faq_3_a')],
                ['q' => __('identity_guide.faq_4_q'), 'a' => __('identity_guide.faq_4_a')],
                ['q' => __('identity_guide.faq_5_q'), 'a' => __('identity_guide.faq_5_a')],
            ] as $faq)
            <div class="card card-pad">
                <strong style="font-size:15px;color:var(--ink)">{{ $faq['q'] }}</strong>
                <p style="font-size:13px;color:var(--muted);margin:8px 0 0;line-height:1.7">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="card card-pad" style="max-width:800px;margin:0 auto;text-align:center">
            <h4 style="margin:0 0 8px;color:var(--ink)">{{ __('identity_guide.cta_title') }}</h4>
            <p style="font-size:14px;color:var(--muted);margin:0 0 18px;line-height:1.7">{{ __('identity_guide.cta_body') }}</p>
            <a href="{{ route('citizen.kyc') }}" class="btn btn-primary btn-lg">{{ __('identity_guide.cta_btn') }}</a>
        </div>
    </div>
</section>

@endsection
