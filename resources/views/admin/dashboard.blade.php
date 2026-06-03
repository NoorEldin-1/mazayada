@extends('layouts.admin')
@section('title', __('admin.nav_dashboard'))
@section('page-title', __('admin.nav_dashboard'))

@section('content')

{{-- Stat Tiles --}}
<div class="tiles-4">
    <div class="tile">
        <div class="ic ic-mint"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div>
            <div class="l">{{ __('admin.stat_total_users') }}</div>
            <div class="v num">{{ $stats['total_users'] }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="ic ic-gold"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div>
            <div class="l">{{ __('admin.stat_pending_kyc') }}</div>
            <div class="v num">{{ $stats['pending_kyc'] }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="ic ic-blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.5 17.5 3 3 3-3"/><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg></div>
        <div>
            <div class="l">{{ __('admin.stat_active_auctions') }}</div>
            <div class="v num">{{ $stats['active_auctions'] }}</div>
        </div>
    </div>
    <div class="tile">
        <div class="ic ic-rose"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div>
            <div class="l">{{ __('admin.stat_total_bids') }}</div>
            <div class="v num">{{ $stats['total_bids'] }}</div>
        </div>
    </div>
</div>

{{-- Recent Auctions --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h">
        <h3>{{ __('admin.recent_auctions') }}</h3>
        <div class="actions"><a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-ghost">{{ __('common.view_all') }}</a></div>
    </div>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr><th>{{ __('admin.th_title') }}</th><th>{{ __('admin.th_entity') }}</th><th>{{ __('admin.th_category') }}</th><th>{{ __('admin.th_price') }}</th><th>{{ __('admin.th_bids') }}</th><th>{{ __('admin.th_status') }}</th></tr>
            </thead>
            <tbody>
                @foreach(\App\Models\Auction::with(['entity','category'])->withCount('bids')->latest()->limit(5)->get() as $auc)
                <tr class="row-hover">
                    <td style="font-weight:600">{{ Str::limit($auc->title_ar, 40) }}</td>
                    <td>{{ $auc->entity?->name ? Str::limit($auc->entity->name, 20) : '—' }}</td>
                    <td>{{ $auc->category?->name ?? '—' }}</td>
                    <td class="num">{{ dzd($auc->opening_price) }}</td>
                    <td class="num">{{ $auc->bids_count }}</td>
                    <td><span class="chip {{ $auc->status->chipClass() }}"><span class="dot"></span>{{ $auc->status->label() }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Users --}}
<div class="card">
    <div class="card-h">
        <h3>{{ __('admin.recent_users') }}</h3>
        <div class="actions"><a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-ghost">{{ __('common.view_all') }}</a></div>
    </div>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr><th>{{ __('admin.th_name') }}</th><th>{{ __('admin.th_email') }}</th><th>{{ __('admin.th_role') }}</th><th>{{ __('admin.th_kyc') }}</th><th>{{ __('admin.th_registered') }}</th></tr>
            </thead>
            <tbody>
                @foreach(\App\Models\User::latest()->limit(5)->get() as $usr)
                <tr class="row-hover">
                    <td style="font-weight:600">{{ $usr->fullNameAr() }}</td>
                    <td class="lat" style="direction:ltr;text-align:start">{{ $usr->email }}</td>
                    <td>{{ $usr->role->label() }}</td>
                    <td><span class="chip {{ $usr->kyc_status->chipClass() }}"><span class="dot"></span>{{ $usr->kyc_status->label() }}</span></td>
                    <td class="num">{{ $usr->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
