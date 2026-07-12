@extends('layouts.admin')

@section('title', __('admin.users.blacklisted_title'))
@section('page-title', __('admin.users.blacklisted_title'))

@section('content')

<div class="flex justify-end mb-5">
    <x-ui.btn variant="ghost" size="sm" :href="route('admin.users.index')">{{ __('admin.users.all_users') }}</x-ui.btn>
</div>

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('admin.th_name') }}</th>
            <th>{{ __('admin.th_email') }}</th>
            <th>{{ __('admin.users.blacklist_reason') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td><a href="{{ route('admin.users.show', $user) }}" class="text-primary font-semibold hover:underline">{{ $user->fullNameAr() }}</a></td>
                <td class="lat" dir="ltr">{{ $user->email }}</td>
                <td>{{ $user->blacklist_reason }}</td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item :action="route('admin.users.unblacklist', $user)"
                            :confirm="__('admin.users.confirm_unblacklist_prompt')" :confirm-label="__('admin.users.unblacklist_action')">{{ __('admin.users.unblacklist_action') }}</x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-8">{{ __('admin.users.no_blacklisted') }}</td></tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $users->links() }}</div>

@endsection
