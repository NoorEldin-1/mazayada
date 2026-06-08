@extends('layouts.admin')

@section('title', __('admin.users.manage_title'))
@section('page-title', __('admin.users.manage_title'))

@section('content')

<div class="flex justify-end mb-5">
    <x-ui.btn variant="danger-ghost" size="sm" :href="route('admin.users.blacklisted')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        {{ __('admin.users.blacklisted_title') }}
    </x-ui.btn>
</div>

<x-ui.table>
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
            <tr>
                <td>
                    <a href="{{ route('admin.users.show', $user) }}" class="text-primary font-semibold hover:underline">{{ $user->fullNameAr() }}</a>
                </td>
                <td class="lat" dir="ltr">{{ $user->email }}</td>
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
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.btn variant="ghost" size="sm" :href="route('admin.users.show', $user)">{{ __('common.view') }}</x-ui.btn>
                        @if(!$user->is_blacklisted)
                            <x-ui.btn variant="danger-ghost" size="sm" type="button"
                                    onclick="document.getElementById('blacklist-{{ $user->id }}').style.display = document.getElementById('blacklist-{{ $user->id }}').style.display === 'none' ? 'block' : 'none'">
                                {{ __('admin.users.blacklist_action') }}
                            </x-ui.btn>
                            <div id="blacklist-{{ $user->id }}" style="display:none;margin-top:0.5rem">
                                <form method="POST" action="{{ route('admin.users.blacklist', $user) }}" data-confirm="{{ __('admin.users.confirm_blacklist_prompt') }}" data-confirm-variant="danger" data-confirm-label="{{ __('admin.users.confirm_blacklist') }}">
                                    @csrf
                                    <div class="field" style="margin-bottom:0.5rem">
                                        <input type="text" name="reason" class="input" placeholder="{{ __('admin.users.blacklist_reason_placeholder') }}" required style="font-size:0.85rem">
                                    </div>
                                    <x-ui.btn variant="danger" size="sm">{{ __('admin.users.confirm_blacklist') }}</x-ui.btn>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.users.unblacklist', $user) }}" style="display:inline"
                                  data-confirm="{{ __('admin.users.confirm_unblacklist_prompt') }}" data-confirm-label="{{ __('admin.users.unblacklist_action') }}">
                                @csrf
                                <x-ui.btn variant="ghost" size="sm" class="text-ok">{{ __('admin.users.unblacklist_action') }}</x-ui.btn>
                            </form>
                            <div class="text-muted" style="font-size:0.8rem;margin-top:0.35rem">{{ $user->blacklist_reason }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-8">{{ __('admin.users.no_users') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

{{-- Pagination --}}
<div class="mt-6">
    {{ $users->links() }}
</div>

@endsection
