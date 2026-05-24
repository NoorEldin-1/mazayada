@extends('layouts.app')

@section('title', 'تصفح المزايدات')

@section('content')
{{-- Page Heading --}}
<div class="page-hd">
    <div class="container">
        <div class="crumbs">
            <a href="/">الرئيسية</a>
            <span class="sep">/</span>
            تصفح المزايدات
        </div>
        <div class="row">
            <div>
                <h1>تصفح المزايدات</h1>
                <div class="meta">إجمالي <span class="num">{{ $auctions->total() }}</span> مزايدة متاحة</div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:48px">

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('auctions.index') }}" class="card" style="margin-bottom:24px">
        <div class="card-pad" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end">
            <div class="field" style="flex:1;min-width:200px">
                <label>بحث</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}" class="input has-ic" placeholder="ابحث عن مزايدة...">
                </div>
            </div>
            <div class="field" style="min-width:160px">
                <label>الفئة</label>
                <select name="category" class="select">
                    <option value="">الكل</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:160px">
                <label>الولاية</label>
                <select name="wilaya" class="select">
                    <option value="">الكل</option>
                    @foreach($wilayas ?? [] as $w)
                        <option value="{{ $w->id }}" {{ request('wilaya') == $w->id ? 'selected' : '' }}>{{ $w->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:140px">
                <label>الحالة</label>
                <select name="status" class="select">
                    <option value="">الكل</option>
                    @foreach(\App\Enums\AuctionStatus::cases() as $s)
                        <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:130px">
                <label>النوع</label>
                <select name="type" class="select">
                    <option value="">الكل</option>
                    @foreach(\App\Enums\AuctionType::cases() as $t)
                        <option value="{{ $t->value }}" {{ request('type') == $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                بحث
            </button>
        </div>
    </form>

    {{-- Auction Grid --}}
    @if($auctions->count())
        <div class="auc-grid">
            @foreach($auctions as $auction)
                <a href="{{ route('auctions.show', $auction) }}" class="auc-card">
                    <div class="auc-img">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5l6 6M3 21l1.5-4.5L17 4a1.41 1.41 0 0 1 2 2L6.5 18.5 3 21z"/><path d="M15 6l3 3"/></svg>
                        @if($auction->isLive())
                            <span class="auc-tag live"><span class="dot"></span> مباشر</span>
                        @else
                            <span class="auc-tag">{{ $auction->status->label() }}</span>
                        @endif
                    </div>
                    <div class="auc-body">
                        <span class="auc-cat">{{ $auction->category->name_ar ?? '' }}</span>
                        <span class="auc-ttl">{{ $auction->title_ar }}</span>
                        <span class="auc-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $auction->wilaya->name_ar ?? $auction->asset_location ?? '--' }}
                        </span>
                    </div>
                    <div class="auc-foot">
                        <div class="pr">
                            <div class="lbl">السعر الحالي</div>
                            <div class="pv num">{{ dzd($auction->currentPrice()) }}</div>
                        </div>
                        <div class="bids">
                            <div class="n num">{{ $auction->bidCount() }}</div>
                            عرض
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div style="margin-top:28px;display:flex;justify-content:center">
            {{ $auctions->withQueryString()->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="card" style="text-align:center;padding:64px 24px">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--muted-2)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 18px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h3 style="margin:0 0 8px;font-size:18px;font-weight:600;color:var(--ink-2)">لا توجد مزايدات</h3>
            <p style="margin:0;color:var(--muted);font-size:14px">لم يتم العثور على أي مزايدات تطابق معايير البحث الخاصة بك.</p>
        </div>
    @endif
</div>
@endsection
