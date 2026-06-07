@extends('layouts.admin')

@section('title', __('admin.users.blacklisted_title'))
@section('page-title', __('admin.users.blacklisted_title'))

@section('content')

<div class="card">
    <div class="card-h">
        <h3>{{ __('admin.users.blacklisted_title') }}</h3>
        <div class="actions">
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">{{ __('admin.users.all_users') }}</a>
        </div>
    </div>

    <table class="tbl">
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
                <tr class="row-hover">
                    <td><a href="{{ route('admin.users.show', $user) }}" style="color:var(--primary);font-weight:600;text-decoration:none">{{ $user->fullNameAr() }}</a></td>
                    <td style="direction:ltr;text-align:start">{{ $user->email }}</td>
                    <td>{{ $user->blacklist_reason }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.unblacklist', $user) }}" style="display:inline"
                              data-confirm="{{ __('admin.users.confirm_unblacklist_prompt') }}" data-confirm-label="{{ __('admin.users.unblacklist_action') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:#1d6045">{{ __('admin.users.unblacklist_action') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.users.no_blacklisted') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.5rem">{{ $users->links() }}</div>

@endsection
