@extends('layouts.admin')

@section('title', 'إدارة المستخدمين')
@section('page-title', 'إدارة المستخدمين')

@section('content')

<div class="card">
    <table class="tbl">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>الدور</th>
                <th>حالة KYC</th>
                <th>حالة الحساب</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="row-hover">
                    <td>{{ $user->fullNameAr() }}</td>
                    <td style="direction:ltr;text-align:right">{{ $user->email }}</td>
                    <td>
                        <span class="chip chip-info">{{ $user->role->label() }}</span>
                    </td>
                    <td>
                        <span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span>
                    </td>
                    <td>
                        @if($user->is_blacklisted)
                            <span class="chip chip-danger">قائمة سوداء</span>
                        @else
                            <span class="chip {{ $user->account_status->chipClass() }}">{{ $user->account_status->label() }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!$user->is_blacklisted)
                            <button type="button" class="btn btn-ghost btn-sm" style="color:var(--red-600)"
                                    onclick="document.getElementById('blacklist-{{ $user->id }}').style.display = document.getElementById('blacklist-{{ $user->id }}').style.display === 'none' ? 'block' : 'none'">
                                قائمة سوداء
                            </button>
                            <div id="blacklist-{{ $user->id }}" style="display:none;margin-top:0.5rem">
                                <form method="POST" action="{{ route('admin.users.blacklist', $user) }}" onsubmit="return confirm('هل أنت متأكد من إدراج هذا المستخدم في القائمة السوداء؟')">
                                    @csrf
                                    <div class="field" style="margin-bottom:0.5rem">
                                        <input type="text" name="reason" class="input" placeholder="سبب الحظر..." required style="font-size:0.85rem">
                                    </div>
                                    <button type="submit" class="btn btn-sm" style="background:var(--red-600);color:#fff">تأكيد الحظر</button>
                                </form>
                            </div>
                        @else
                            <span style="font-size:0.8rem;color:var(--ink-muted)">{{ $user->blacklist_reason }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:2rem;color:var(--ink-muted)">لا يوجد مستخدمون</td>
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
