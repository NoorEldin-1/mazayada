@extends('layouts.admin')

@section('title', __('appeals.manage_title'))
@section('page-title', __('appeals.manage_title'))

@section('content')

@if(session('success'))
    <div class="mb-5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm">{{ session('error') }}</div>
@endif

@php
    // Both platform staff and entity accounts use this page; the available
    // actions differ. Entity accounts only ever act on a forwarded appeal.
    $isEntity = auth()->user()->entity_id !== null;
@endphp

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
            @php $s = $appeal->status->value; @endphp
            <tr>
                <td class="num" style="font-size:0.8rem">{{ Str::limit($appeal->id, 8, '...') }}</td>
                <td>{{ $appeal->user?->fullNameAr() ?? '—' }}</td>
                <td>{{ $appeal->auction?->title_ar ?? '—' }}</td>
                <td>{{ $appeal->subject }}</td>
                <td><span class="chip {{ $appeal->status->chipClass() }}">{{ $appeal->status->label() }}</span></td>
                <td>{{ $appeal->created_at->format('Y-m-d') }}</td>
                <td>
                    <x-ui.action-menu>
                        <x-ui.action-menu.item data-modal-target="#appeal-{{ $appeal->id }}">{{ __('common.view') }}</x-ui.action-menu.item>
                    </x-ui.action-menu>

                    <x-ui.modal id="appeal-{{ $appeal->id }}" :title="$appeal->subject" size="lg">
                        {{-- Context: the citizen's appeal --}}
                        <p style="font-size:0.9rem;margin-bottom:0.6rem"><strong>{{ __('appeals.reason_label') }}</strong> {{ $appeal->reason }}</p>

                        {{-- The entity's decision, once it has decided --}}
                        @if($appeal->entity_decision)
                            <div style="margin:0.5rem 0 0.75rem;padding:0.6rem 0.85rem;border-radius:0.6rem;background:var(--bg-2)">
                                <p style="font-size:0.85rem;margin-bottom:0.25rem">
                                    <strong>{{ __('appeals.entity_decision_label') }}</strong>
                                    <span class="chip {{ $appeal->entity_decision->publicChipClass() }}">{{ $appeal->entity_decision->label() }}</span>
                                </p>
                                @if($appeal->entity_response)
                                    <p style="font-size:0.85rem;color:var(--ink-2)"><strong>{{ __('appeals.entity_response_label') }}</strong> {{ $appeal->entity_response }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- The platform's final note, once terminal --}}
                        @if($appeal->status->isTerminal() && $appeal->admin_response)
                            <p style="font-size:0.85rem;margin:0.5rem 0"><strong>{{ __('appeals.response_label') }}:</strong> {{ $appeal->admin_response }}</p>
                        @endif

                        {{-- ===== Stage-appropriate actions ===== --}}
                        @if($isEntity)
                            {{-- Organising entity: decide a forwarded appeal --}}
                            @if($s === 'FORWARDED_TO_ENTITY')
                                @can('decide', $appeal)
                                    <form method="POST" action="{{ route('admin.appeals.decide', $appeal) }}" style="margin-top:0.75rem">
                                        @csrf
                                        <div class="field" style="margin-bottom:0.5rem">
                                            <label style="font-size:0.85rem;display:block;margin-bottom:.3rem">{{ __('appeals.entity_response_field') }}</label>
                                            <textarea name="entity_response" class="textarea" rows="3" required placeholder="{{ __('appeals.entity_response_placeholder') }}"></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <x-ui.btn variant="primary" size="sm" name="decision" value="APPROVED">{{ __('appeals.decision_approve') }}</x-ui.btn>
                                            <x-ui.btn variant="danger" size="sm" name="decision" value="REJECTED">{{ __('appeals.decision_reject') }}</x-ui.btn>
                                        </div>
                                    </form>
                                @endcan
                            @else
                                <p class="text-muted" style="font-size:0.85rem;margin-top:0.5rem">{{ __('appeals.entity_no_action') }}</p>
                            @endif
                        @else
                            {{-- Platform admin --}}
                            @if($s === 'PENDING')
                                <div style="margin-top:0.75rem">
                                    @can('forward', $appeal)
                                        <form method="POST" action="{{ route('admin.appeals.forward', $appeal) }}" style="margin-bottom:0.75rem">
                                            @csrf
                                            <x-ui.btn variant="primary" size="sm">{{ __('appeals.forward_btn') }}</x-ui.btn>
                                        </form>
                                    @endcan
                                    @can('rejectAtIntake', $appeal)
                                        <form method="POST" action="{{ route('admin.appeals.reject', $appeal) }}">
                                            @csrf
                                            <div class="field" style="margin-bottom:0.5rem">
                                                <label style="font-size:0.85rem;display:block;margin-bottom:.3rem">{{ __('appeals.reject_intake_label') }}</label>
                                                <textarea name="admin_response" class="textarea" rows="2" required placeholder="{{ __('appeals.response_placeholder') }}"></textarea>
                                            </div>
                                            <x-ui.btn variant="danger" size="sm">{{ __('appeals.reject_intake_btn') }}</x-ui.btn>
                                        </form>
                                    @endcan
                                </div>
                            @elseif($s === 'FORWARDED_TO_ENTITY')
                                <p class="text-muted" style="font-size:0.85rem;margin-top:0.5rem">{{ __('appeals.awaiting_entity') }}</p>
                            @elseif($s === 'ENTITY_APPROVED' || $s === 'ENTITY_REJECTED')
                                @can('confirm', $appeal)
                                    <form method="POST" action="{{ route('admin.appeals.confirm', $appeal) }}" style="margin-top:0.75rem">
                                        @csrf
                                        <div class="field" style="margin-bottom:0.5rem">
                                            <label style="font-size:0.85rem;display:block;margin-bottom:.3rem">{{ __('appeals.final_decision_label') }}</label>
                                            <select name="decision" class="input">
                                                <option value="APPROVED" @selected($appeal->entity_decision?->value === 'APPROVED')>{{ __('appeals.decision_approve') }}</option>
                                                <option value="REJECTED" @selected($appeal->entity_decision?->value === 'REJECTED')>{{ __('appeals.decision_reject') }}</option>
                                            </select>
                                        </div>
                                        <div class="field" style="margin-bottom:0.5rem">
                                            <label style="font-size:0.85rem;display:block;margin-bottom:.3rem">{{ __('appeals.response_label') }}</label>
                                            <textarea name="admin_response" class="textarea" rows="2" required placeholder="{{ __('appeals.response_placeholder') }}"></textarea>
                                        </div>
                                        <x-ui.btn variant="primary" size="sm">{{ __('appeals.confirm_btn') }}</x-ui.btn>
                                    </form>
                                @endcan
                            @endif
                        @endif
                    </x-ui.modal>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-8">{{ __('appeals.none') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">
    {{ $appeals->links() }}
</div>

@endsection
