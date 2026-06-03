@extends('layouts.admin')

@section('title', __('admin.kyc.manage_title'))
@section('page-title', __('admin.kyc.manage_title'))

@section('content')

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">
    {{ session('success') }}
</div>
@endif

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('admin.th_name') }}</th>
                <th>NIN</th>
                <th>{{ __('admin.kyc.th_email_short') }}</th>
                <th>{{ __('admin.kyc.th_submitted_date') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="row-hover">
                    <td>{{ $user->fullNameAr() }}</td>
                    <td class="num" style="direction:ltr;text-align:right">{{ $user->nin }}</td>
                    <td style="direction:ltr;text-align:right">{{ $user->email }}</td>
                    <td>{{ $user->kyc_submitted_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.kyc.show', $user) }}" class="btn btn-sm" style="background:#15573f;color:#fff">
                            {{ __('admin.kyc.review') }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.kyc.no_pending') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $users->links() }}
</div>

@endsection
