@extends('layouts.admin')

@section('title', 'إدارة الطعون')
@section('page-title', 'إدارة الطعون')

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>الرقم</th>
                <th>المستخدم</th>
                <th>المزايدة</th>
                <th>الموضوع</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appeals as $appeal)
                <tr class="row-hover">
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
                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="document.getElementById('respond-{{ $appeal->id }}').style.display = document.getElementById('respond-{{ $appeal->id }}').style.display === 'none' ? 'block' : 'none'">
                                الرد
                            </button>

                            <div id="respond-{{ $appeal->id }}" style="display:none;margin-top:0.75rem">
                                <div class="card card-pad" style="background:var(--bg-subtle)">
                                    <p style="font-size:0.85rem;margin-bottom:0.5rem"><strong>السبب:</strong> {{ $appeal->reason }}</p>

                                    <form method="POST" action="{{ route('admin.appeals.respond', $appeal) }}">
                                        @csrf
                                        <div class="field" style="margin-bottom:0.75rem">
                                            <label for="admin_response_{{ $appeal->id }}" style="font-size:0.85rem">الرد</label>
                                            <textarea id="admin_response_{{ $appeal->id }}" name="admin_response" class="textarea" rows="3" required placeholder="اكتب ردك هنا..."></textarea>
                                        </div>
                                        <div style="display:flex;gap:0.5rem">
                                            <button type="submit" name="status" value="RESOLVED" class="btn btn-sm" style="background:#10b981;color:#fff">قبول الطعن</button>
                                            <button type="submit" name="status" value="REJECTED" class="btn btn-sm" style="background:#ef4444;color:#fff">رفض الطعن</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            @if($appeal->admin_response)
                                <span style="font-size:0.8rem;color:var(--ink-muted)">{{ Str::limit($appeal->admin_response, 40) }}</span>
                            @else
                                <span style="color:var(--ink-muted)">—</span>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-muted)">لا توجد طعون</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $appeals->links() }}
</div>

@endsection
