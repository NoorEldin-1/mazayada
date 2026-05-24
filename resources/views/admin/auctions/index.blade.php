@extends('layouts.admin')

@section('title', 'إدارة المزايدات')
@section('page-title', 'إدارة المزايدات')

@section('content')

{{-- Header Actions --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
    <div></div>
    <a href="{{ route('admin.auctions.create') }}" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        إنشاء مزايدة
    </a>
</div>

{{-- Filter Tabs --}}
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <a href="{{ route('admin.auctions.index') }}"
       class="chip {{ !request('status') ? 'chip-info' : 'chip-muted' }}">
        الكل
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'DRAFT']) }}"
       class="chip {{ request('status') === 'DRAFT' ? 'chip-info' : 'chip-muted' }}">
        مسودة
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'PUBLISHED']) }}"
       class="chip {{ request('status') === 'PUBLISHED' ? 'chip-info' : 'chip-muted' }}">
        منشورة
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'ACTIVE']) }}"
       class="chip {{ request('status') === 'ACTIVE' ? 'chip-info' : 'chip-muted' }}">
        نشطة
    </a>
    <a href="{{ route('admin.auctions.index', ['status' => 'CLOSED']) }}"
       class="chip {{ request('status') === 'CLOSED' ? 'chip-info' : 'chip-muted' }}">
        مغلقة
    </a>
</div>

{{-- Auctions Table --}}
<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>العنوان</th>
                <th>الجهة</th>
                <th>الفئة</th>
                <th>السعر</th>
                <th>العروض</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($auctions as $auction)
                <tr class="row-hover">
                    <td>{{ $auction->title_ar }}</td>
                    <td>{{ $auction->entity?->name_ar ?? '—' }}</td>
                    <td>{{ $auction->category?->name_ar ?? '—' }}</td>
                    <td class="num">{{ dzd($auction->opening_price) }}</td>
                    <td class="num">{{ $auction->bidCount() }}</td>
                    <td>
                        <span class="chip {{ $auction->status->chipClass() }}">{{ $auction->status->label() }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.375rem;flex-wrap:wrap">
                            @if($auction->status === \App\Enums\AuctionStatus::DRAFT)
                                {{-- Publish --}}
                                <form method="POST" action="{{ route('admin.auctions.publish', $auction) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">نشر</button>
                                </form>
                                {{-- Edit --}}
                                <a href="{{ route('admin.auctions.edit', $auction) }}" class="btn btn-ghost btn-sm">تعديل</a>
                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.auctions.destroy', $auction) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه المزايدة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red-600)">حذف</button>
                                </form>
                            @elseif($auction->status === \App\Enums\AuctionStatus::PUBLISHED)
                                {{-- Start --}}
                                <form method="POST" action="{{ route('admin.auctions.start', $auction) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-accent btn-sm">بدء</button>
                                </form>
                            @else
                                <a href="{{ route('auctions.show', $auction) }}" class="btn btn-ghost btn-sm">عرض</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-muted)">لا توجد مزايدات</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $auctions->links() }}
</div>

@endsection
