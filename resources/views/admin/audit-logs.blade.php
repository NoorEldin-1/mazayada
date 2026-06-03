@extends('layouts.admin')

@section('title', __('admin.nav_audit'))
@section('page-title', __('admin.nav_audit'))

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('admin.audit.th_time') }}</th>
                <th>{{ __('admin.audit.th_actor') }}</th>
                <th>{{ __('admin.th_role') }}</th>
                <th>{{ __('admin.audit.th_action') }}</th>
                <th>{{ __('admin.audit.th_resource') }}</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr class="row-hover">
                    <td style="white-space:nowrap;font-size:0.85rem">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td class="num" style="font-size:0.8rem">{{ Str::limit($log->actor_id, 12, '...') }}</td>
                    <td>
                        <span class="chip chip-muted" style="font-size:0.75rem">{{ $log->actor_role }}</span>
                    </td>
                    <td>
                        <span class="chip chip-info">{{ $log->action }}</span>
                    </td>
                    <td style="font-size:0.8rem">
                        {{ $log->resource_type }}
                        <span class="num" style="color:var(--ink-muted);font-size:0.75rem">#{{ Str::limit($log->resource_id, 8, '...') }}</span>
                    </td>
                    <td class="num" style="font-size:0.8rem;direction:ltr;text-align:right">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('admin.audit.no_logs') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $logs->links() }}
</div>

@endsection
