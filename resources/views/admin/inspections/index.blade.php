@extends('layouts.admin')

@section('title', __('inspections.manage_title'))
@section('page-title', __('inspections.manage_title'))

@section('content')

<div class="card">
    <table class="tbl">
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
                <tr class="row-hover">
                    <td>{{ $question->auction?->title_ar ?? '—' }}</td>
                    <td>{{ Str::limit($question->question, 80) }}</td>
                    <td><span class="chip {{ $question->status->chipClass() }}">{{ $question->status->label() }}</span></td>
                    <td>{{ $question->created_at->format('Y-m-d') }}</td>
                    <td>
                        @if($question->status->value === 'PENDING')
                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="document.getElementById('ans-{{ $question->id }}').style.display = document.getElementById('ans-{{ $question->id }}').style.display === 'none' ? 'block' : 'none'">
                                {{ __('inspections.answer') }}
                            </button>
                            <div id="ans-{{ $question->id }}" style="display:none;margin-top:0.75rem">
                                <div class="card card-pad" style="background:var(--bg-subtle)">
                                    <form method="POST" action="{{ route('admin.inspections.answer', $question) }}">
                                        @csrf
                                        <div class="field" style="margin-bottom:0.75rem">
                                            <label style="font-size:0.85rem">{{ __('inspections.answer_label') }}</label>
                                            <textarea name="answer" class="textarea" rows="3" required></textarea>
                                        </div>
                                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;margin-bottom:.6rem">
                                            <input type="checkbox" name="is_public" value="1" checked> {{ __('inspections.public') }}
                                        </label>
                                        <div style="display:flex;gap:0.5rem">
                                            <button type="submit" class="btn btn-primary btn-sm">{{ __('inspections.submit_answer') }}</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.inspections.reject', $question) }}" style="margin-top:.5rem">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm">{{ __('inspections.reject') }}</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <span style="font-size:0.8rem;color:var(--ink-muted)">{{ Str::limit($question->answer, 50) ?: '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">{{ __('inspections.none') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.5rem">{{ $questions->links() }}</div>

@endsection
