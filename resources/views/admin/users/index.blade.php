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
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :href="route('admin.users.show', $user)">{{ __('common.view') }}</x-ui.action-menu.item>
                        @if(!$user->is_blacklisted)
                            <x-ui.action-menu.item data-modal-target="#blacklist-{{ $user->id }}" variant="danger">{{ __('admin.users.blacklist_action') }}</x-ui.action-menu.item>
                        @else
                            <x-ui.action-menu.item :action="route('admin.users.unblacklist', $user)"
                                :confirm="__('admin.users.confirm_unblacklist_prompt')" :confirm-label="__('admin.users.unblacklist_action')">{{ __('admin.users.unblacklist_action') }}</x-ui.action-menu.item>
                        @endif
                    </x-ui.action-menu>
                    @if($user->is_blacklisted && $user->blacklist_reason)
                        <div class="text-muted" style="font-size:0.8rem;margin-top:0.35rem">{{ $user->blacklist_reason }}</div>
                    @endif

                    @if(!$user->is_blacklisted)
                        <x-ui.modal id="blacklist-{{ $user->id }}" :title="__('admin.users.blacklist_action')">
                            <form method="POST" action="{{ route('admin.users.blacklist', $user) }}">
                                @csrf
                                <div class="field" style="margin-bottom:0.9rem">
                                    <label class="text-sm text-muted" style="display:block;margin-bottom:0.35rem">{{ __('admin.users.blacklist_reason') }}</label>
                                    <input type="text" name="reason" class="input" placeholder="{{ __('admin.users.blacklist_reason_placeholder') }}" required>
                                </div>
                                <div class="flex gap-2">
                                    <x-ui.btn variant="danger" size="sm">{{ __('admin.users.confirm_blacklist') }}</x-ui.btn>
                                    <x-ui.btn variant="ghost" size="sm" type="button" data-modal-close>{{ __('common.cancel') }}</x-ui.btn>
                                </div>
                            </form>
                        </x-ui.modal>
                    @endif
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
