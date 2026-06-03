@extends('layouts.app')
@section('title', __('nav.how_it_works'))
@section('content')

<section style="padding:48px 0 72px">
    <div class="container">
        <div class="sect-head">
            <div class="sect-eyebrow">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                {{ __('pages.hiw.eyebrow') }}
            </div>
            <h2>{{ __('pages.hiw.title_pre') }} <span class="hl">{{ __('pages.hiw.title_hl') }}</span> {{ __('pages.hiw.title_post') }}</h2>
            <p>{{ __('pages.hiw.subtitle') }}</p>
        </div>

        <div class="steps" style="grid-template-columns:repeat(3,1fr);gap:32px;margin-bottom:64px">
            @foreach([
                ['n' => '1', 'title' => __('pages.hiw.step_1_title'), 'desc' => __('pages.hiw.step_1_desc')],
                ['n' => '2', 'title' => __('pages.hiw.step_2_title'), 'desc' => __('pages.hiw.step_2_desc'), 'alt' => true],
                ['n' => '3', 'title' => __('pages.hiw.step_3_title'), 'desc' => __('pages.hiw.step_3_desc')],
                ['n' => '4', 'title' => __('pages.hiw.step_4_title'), 'desc' => __('pages.hiw.step_4_desc'), 'alt' => true],
                ['n' => '5', 'title' => __('pages.hiw.step_5_title'), 'desc' => __('pages.hiw.step_5_desc')],
                ['n' => '6', 'title' => __('pages.hiw.step_6_title'), 'desc' => __('pages.hiw.step_6_desc'), 'alt' => true],
            ] as $step)
            <div class="step {{ ($step['alt'] ?? false) ? 'alt' : '' }}" style="position:relative">
                <div class="n">{{ $step['n'] }}</div>
                <h4>{{ $step['title'] }}</h4>
                <p>{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- FAQ Section --}}
        <div class="sect-head">
            <h2>{{ __('pages.hiw.faq_pre') }} <span class="hl">{{ __('pages.hiw.faq_hl') }}</span></h2>
        </div>
        <div style="max-width:800px;margin:0 auto;display:grid;gap:14px">
            @foreach([
                ['q' => __('pages.hiw.faq_1_q'), 'a' => __('pages.hiw.faq_1_a')],
                ['q' => __('pages.hiw.faq_2_q'), 'a' => __('pages.hiw.faq_2_a')],
                ['q' => __('pages.hiw.faq_3_q'), 'a' => __('pages.hiw.faq_3_a')],
                ['q' => __('pages.hiw.faq_4_q'), 'a' => __('pages.hiw.faq_4_a')],
                ['q' => __('pages.hiw.faq_5_q'), 'a' => __('pages.hiw.faq_5_a')],
            ] as $faq)
            <div class="card card-pad" style="cursor:pointer">
                <strong style="font-size:15px;color:var(--ink)">{{ $faq['q'] }}</strong>
                <p style="font-size:13px;color:var(--muted);margin:8px 0 0;line-height:1.7">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
