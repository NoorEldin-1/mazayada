@extends('layouts.admin')

@section('title', __('appeals.manage_title'))
@section('page-title', __('appeals.manage_title'))

@section('content')

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('appeals.th_id') }}</th>
            <th>{{ __('appeals.th_user') }}</th>
            <th>{{ __('appeals.th_auction') }}</th>
            <th>{{ __('appeals.th_subject') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('common.date') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($appeals as $appeal)
            <tr>
                <td class="num" style="font-size:0.8rem">{{ Str::limit($appeal->id, 8, '...') }}</td>
                <td>{{ $appeal->user?->fullNameAr() ?? '—' }}</td>
                <td>{{ $appeal->auction?->title_ar ?? '—' }}</td>
                <td>{{ $appeal->subject }}</td>
                <td>
                    <span class="chip {{ $appeal->status->chipClass() }}">{{ $appeal->status->label() }}</span>
                </td>
                <td>{{ $appeal->created_at->format('Y-m-d') }}</td>
                <td>
                    @if(in_array($appeal->status->value, ['SUBMITTED', 'UNDER_REVIEW']))
                        <x-ui.btn variant="ghost" size="sm" type="button"
                                onclick="document.getElementById('respond-{{ $appeal->id }}').style.display = document.getElementById('respond-{{ $appeal->id }}').style.display === 'none' ? 'block' : 'none'">
                            {{ __('appeals.respond') }}
                        </x-ui.btn>

                        <div id="respond-{{ $appeal->id }}" style="display:none;margin-top:0.75rem">
                            <x-ui.card>
                                <p style="font-size:0.85rem;margin-bottom:0.5rem"><strong>{{ __('appeals.reason_label') }}</strong> {{ $appeal->reason }}</p>

                                <form method="POST" action="{{ route('admin.appeals.respond', $appeal) }}">
                                    @csrf
                                    <div class="field" style="margin-bottom:0.75rem">
                                        <label for="admin_response_{{ $appeal->id }}" style="font-size:0.85rem">{{ __('appeals.response_label') }}</label>
                                        <textarea id="admin_response_{{ $appeal->id }}" name="admin_response" class="textarea" rows="3" required placeholder="{{ __('appeals.response_placeholder') }}"></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <x-ui.btn variant="primary" size="sm" name="status" value="RESOLVED">{{ __('appeals.accept') }}</x-ui.btn>
                                        <x-ui.btn variant="danger" size="sm" name="status" value="REJECTED">{{ __('appeals.reject') }}</x-ui.btn>
                                    </div>
                                </form>
                            </x-ui.card>
                        </div>
                    @else
                        @if($appeal->admin_response)
                            <span class="text-muted" style="font-size:0.8rem">{{ Str::limit($appeal->admin_response, 40) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-8">{{ __('appeals.none') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

{{-- Pagination --}}
<div class="mt-6">
    {{ $appeals->links() }}
</div>

@endsection
