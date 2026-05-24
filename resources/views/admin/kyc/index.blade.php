@extends('layouts.admin')

@section('title', 'طلبات التحقق من الهوية')
@section('page-title', 'طلبات التحقق من الهوية')

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>NIN</th>
                <th>البريد</th>
                <th>تاريخ التسجيل</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="row-hover">
                    <td>{{ $user->fullNameAr() }}</td>
                    <td class="num" style="direction:ltr;text-align:right">{{ $user->nin }}</td>
                    <td style="direction:ltr;text-align:right">{{ $user->email }}</td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div style="display:flex;gap:0.375rem;align-items:flex-start;flex-wrap:wrap">
                            {{-- Approve --}}
                            <form method="POST" action="{{ route('admin.kyc.approve', $user) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm" style="background:#10b981;color:#fff">قبول</button>
                            </form>

                            {{-- Reject Toggle --}}
                            <button type="button" class="btn btn-sm" style="background:#ef4444;color:#fff"
                                    onclick="document.getElementById('reject-{{ $user->id }}').style.display = document.getElementById('reject-{{ $user->id }}').style.display === 'none' ? 'block' : 'none'">
                                رفض
                            </button>
                        </div>

                        {{-- Reject Form --}}
                        <div id="reject-{{ $user->id }}" style="display:none;margin-top:0.5rem">
                            <form method="POST" action="{{ route('admin.kyc.reject', $user) }}">
                                @csrf
                                <div class="field" style="margin-bottom:0.5rem">
                                    <input type="text" name="reason" class="input" placeholder="سبب الرفض..." required style="font-size:0.85rem">
                                </div>
                                <button type="submit" class="btn btn-sm" style="background:#ef4444;color:#fff">تأكيد الرفض</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">لا توجد طلبات تحقق معلّقة</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top:1.5rem">
    {{ $users->links() }}
</div>

@endsection
