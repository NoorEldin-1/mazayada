@extends('layouts.admin')

@section('title', __('inspections.manage_title'))
@section('page-title', __('inspections.manage_title'))

@section('content')

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('inspections.th_auction') }}</th>
            <th>{{ __('inspections.th_question') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('common.date') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($questions as $question)
            <tr>
                <td>{{ $question->auction?->title_ar ?? '—' }}</td>
                <td>{{ Str::limit($question->question, 80) }}</td>
                <td><span class="chip {{ $question->status->chipClass() }}">{{ $question->status->label() }}</span></td>
                <td>{{ $question->created_at->format('Y-m-d') }}</td>
                <td>
                    @if($question->status->value === 'PENDING')
                        <x-ui.action-menu>
                            <x-ui.action-menu.item data-modal-target="#inspection-{{ $question->id }}">{{ __('inspections.answer') }}</x-ui.action-menu.item>
                        </x-ui.action-menu>

                        <x-ui.modal id="inspection-{{ $question->id }}" :title="__('inspections.answer')">
                            <form method="POST" action="{{ route('admin.inspections.answer', $question) }}">
                                @csrf
                                <div class="field" style="margin-bottom:0.75rem">
                                    <label style="font-size:0.85rem;display:block;margin-bottom:.3rem">{{ __('inspections.answer_label') }}</label>
                                    <textarea name="answer" class="textarea" rows="3" required></textarea>
                                </div>
                                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;margin-bottom:.9rem">
                                    <input type="checkbox" name="is_public" value="1" checked> {{ __('inspections.public') }}
                                </label>
                                <div class="flex gap-2">
                                    <x-ui.btn variant="primary" size="sm">{{ __('inspections.submit_answer') }}</x-ui.btn>
                                    <x-ui.btn variant="ghost" size="sm" type="button" data-modal-close>{{ __('common.cancel') }}</x-ui.btn>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.inspections.reject', $question) }}" style="margin-top:1rem;border-block-start:1px solid var(--line);padding-top:1rem">
                                @csrf
                                <x-ui.btn variant="danger-ghost" size="sm">{{ __('inspections.reject') }}</x-ui.btn>
                            </form>
                        </x-ui.modal>
                    @else
                        <span class="text-muted" style="font-size:0.8rem">{{ Str::limit($question->answer, 50) ?: '—' }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-8">{{ __('inspections.none') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">{{ $questions->links() }}</div>

@endsection
