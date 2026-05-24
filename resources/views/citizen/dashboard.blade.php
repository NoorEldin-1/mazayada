@extends('layouts.citizen')

@section('title', 'لوحة التحكم')

@section('content')
{{-- Stat Tiles --}}
<div class="tiles-3">
    <div class="tile">
        <div class="ic ic-mint">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="l">مزايدات نشطة</div>
            <div class="v num">{{ $activeCount ?? 0 }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="ic ic-gold">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div>
            <div class="l">فائز</div>
            <div class="v num">{{ $wonCount ?? 0 }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="ic ic-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div>
            <div class="l">إجمالي المشاركات</div>
            <div class="v num">{{ $totalParticipations ?? 0 }}</div>
        </div>
    </div>
</div>

{{-- KYC Status Card --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-h">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
        <h3>حالة التحقق من الهوية</h3>
        <div class="actions">
            <span class="chip {{ auth()->user()->kyc_status->chipClass() }}">
                <span class="dot"></span>
                {{ auth()->user()->kyc_status->label() }}
            </span>
        </div>
    </div>
    <div class="card-pad">
        @if(!auth()->user()->isKycComplete())
            <p style="margin:0 0 14px;font-size:14px;color:var(--ink-2)">
                يجب إكمال عملية التحقق من الهوية للمشاركة في المزايدات. أكمل الخطوات المطلوبة الآن.
            </p>
            <a href="{{ route('citizen.kyc') }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                إكمال التحقق
            </a>
        @else
            <p style="margin:0;font-size:14px;color:var(--ok);font-weight:500">
                تم التحقق من هويتك بنجاح. يمكنك المشاركة في المزايدات.
            </p>
        @endif
    </div>
</div>

{{-- Won Auctions --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-h">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <h3>المزايدات التي فزت بها</h3>
    </div>
    @if(isset($wonAuctions) && $wonAuctions->count())
        <div style="padding:8px 0">
            @foreach($wonAuctions as $auction)
                <a href="{{ route('auctions.show', $auction) }}" style="display:flex;align-items:center;gap:14px;padding:14px 24px;{{ !$loop->last ? 'border-bottom:1px solid var(--line)' : '' }}">
                    <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#FAEFD0,#E5F3EC);display:grid;place-items:center;flex-shrink:0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:600;margin-bottom:2px">{{ $auction->title_ar }}</div>
                        <div style="font-size:12px;color:var(--muted)">{{ $auction->updated_at->format('Y-m-d') }}</div>
                    </div>
                    <div class="num" style="font-weight:700;color:var(--primary);font-size:15px">{{ dzd($auction->final_price ?? $auction->currentPrice()) }}</div>
                </a>
            @endforeach
        </div>
    @else
        <div class="card-pad" style="text-align:center;color:var(--muted);padding:32px;font-size:14px">
            لم تفز بأي مزايدة بعد
        </div>
    @endif
</div>

{{-- Quick Actions --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
    <a href="{{ route('citizen.appeals') }}" class="card card-pad" style="display:flex;align-items:center;gap:14px;transition:all .15s">
        <div style="width:46px;height:46px;border-radius:13px;background:#EDE6F8;color:#6B45B7;display:grid;place-items:center;flex-shrink:0">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div style="flex:1">
            <div style="font-size:15px;font-weight:600;margin-bottom:2px">طعوناتي</div>
            <div style="font-size:12px;color:var(--muted)">إدارة الطعون والشكاوى</div>
        </div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <a href="{{ route('auctions.index') }}" class="card card-pad" style="display:flex;align-items:center;gap:14px;transition:all .15s">
        <div style="width:46px;height:46px;border-radius:13px;background:#E5F3EC;color:var(--primary-2);display:grid;place-items:center;flex-shrink:0">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
        </div>
        <div style="flex:1">
            <div style="font-size:15px;font-weight:600;margin-bottom:2px">المزايدات المباشرة</div>
            <div style="font-size:12px;color:var(--muted)">تصفح المزايدات النشطة الآن</div>
        </div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</div>
@endsection
