@extends('layouts.admin')

@section('title', __('admin.users.manage_title'))
@section('page-title', __('admin.users.manage_title'))

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('admin.th_name') }}</th>
                <th>{{ __('admin.th_email') }}</th>
                <th>{{ __('admin.th_role') }}</th>
                <th>{{ __('admin.th_kyc') }}</th>
                <th>{{ __('admin.users.th_account_status') }}</th>
                <th>{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="row-hover">
                    <td>{{ $user->fullNameAr() }}</td>
                    <td style="direction:ltr;text-align:right">{{ $user->email }}</td>
                    <td>
                        <span class="chip chip-info">{{ $user->role->label() }}</span>
                    </td>
                    <td>
                        <span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span>
                    </td>
                    <td>
                        @if($user->is_blacklisted)
                            <span class="chip chip-danger">{{ __('admin.users.blacklisted') }}</span>
                        @else
                            <span class="chip {{ $user->account_status->chipClass() }}">{{ $user->account_status->label() }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!$user->is_blacklisted)
                            <button type="button" class="btn btn-ghost btn-sm" style="color:var(--red-600)"
                                    onclick="document.getElementById('blacklist-{{ $user->id }}').style.display = document.getElementById('blacklist-{{ $user->id }}').style.display === 'none' ? 'block' : 'none'">
                                {{ __('admin.users.blacklist_action') }}
                            </button>
                            <div id="blacklist-{{ $user->id }}" style="display:none;margin-top:0.5rem">
                                <form method="POST" action="{{ route('admin.users.blacklist', $user) }}" onsubmit="return confirm('{{ __('admin.users.confirm_blacklist_prompt') }}')">
                                    @csrf
                                    <div class="field" style="margin-bottom:0.5rem">
                                        <input type="text" name="reason" class="input" placeholder="{{ __('admin.users.blacklist_reason_placeholder') }}" required style="font-size:0.85rem">
                                    </div>
                                    <button type="submit" class="btn btn-sm" style="background:var(--red-600);color:#fff">{{ __('admin.users.confirm_blacklist') }}</button>
                                </form>
                            </div>
                        @else
                            <span style="font-size:0.8rem;color:var(--ink-muted)">{{ $user->blacklist_reason }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.users.no_users') }}</td>
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
