@extends('layouts.admin')

@section('title', __('admin.kyc.review_title'))
@section('page-title', __('admin.kyc.review_title'))

@section('content')

@php
    $docs = ['id-front' => __('kyc.doc_id_front'), 'id-back' => __('kyc.doc_id_back'), 'selfie-with-id' => __('kyc.doc_selfie')];
    $bio = $user->biometrics;
    $rows = [
        'kyc.f_first_name_fr' => $user->first_name_fr,
        'kyc.f_last_name_fr' => $user->last_name_fr,
        'admin.kyc.f_name_ar' => $user->fullNameAr(),
        'kyc.f_father_name' => $user->father_name,
        'kyc.f_mother_fullname' => $user->mother_fullname,
        'admin.kyc.f_birth_date' => $user->birth_date?->format('Y-m-d'),
        'kyc.f_wilaya' => $user->commune?->wilaya?->name,
        'kyc.f_commune' => $user->commune?->name,
        'kyc.f_full_address' => $user->address,
        'kyc.f_postal_code' => $user->postal_code,
        'kyc.f_profession' => $user->profession,
        'kyc.f_expected_income' => $user->expected_income ? number_format($user->expected_income, 0, ',', ' ').' '.__('common.currency') : null,
        'kyc.f_rip' => $user->rip,
    ];
@endphp

<div style="margin-bottom:18px">
    <a href="{{ route('admin.kyc.index') }}" style="font-size:13px;color:var(--ink-muted);text-decoration:none">← {{ __('admin.kyc.back_to_queue') }}</a>
</div>

@if($errors->any())
<div style="background:#FBE2E0;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">
    {{ $errors->first() }}
</div>
@endif

{{-- Identity header --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h" style="display:flex;align-items:center;justify-content:space-between">
        <h3>{{ $user->fullNameAr() }} <span style="color:var(--ink-muted);font-weight:400">— {{ $user->fullNameFr() }}</span></h3>
        <span class="chip {{ $user->kyc_status->chipClass() }}"><span class="dot"></span>{{ $user->kyc_status->label() }}</span>
    </div>
    <div class="card-pad" style="display:flex;gap:24px;flex-wrap:wrap;font-size:13px;color:var(--ink-muted)">
        <span>NIN: <strong style="color:var(--ink);direction:ltr">{{ $user->nin }}</strong></span>
        <span>{{ __('admin.kyc.th_email_short') }}: <strong style="color:var(--ink);direction:ltr">{{ $user->email }}</strong></span>
        <span>{{ __('admin.kyc.th_submitted_date') }}: <strong style="color:var(--ink)">{{ $user->kyc_submitted_at?->format('Y-m-d H:i') }}</strong></span>
    </div>
</div>

{{-- Documents --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('admin.kyc.documents_title') }}</h3></div>
    <div class="card-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
            @foreach($docs as $type => $label)
            @php $field = str_replace('-', '_', $type) . '_path'; $uploaded = $bio && $bio->$field; @endphp
            <div style="border:1px solid var(--line);border-radius:12px;overflow:hidden">
                <div style="padding:10px 14px;font-size:13px;font-weight:600;background:#f8faf9">{{ $label }}</div>
                @if($uploaded)
                    <a href="{{ route('admin.kyc.document', [$user, $type]) }}" target="_blank">
                        <img src="{{ route('admin.kyc.document', [$user, $type]) }}" alt="{{ $label }}" style="width:100%;display:block;max-height:220px;object-fit:cover">
                    </a>
                @else
                    <div style="padding:40px;text-align:center;color:var(--ink-muted);font-size:13px">{{ __('admin.kyc.no_document') }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Personal info --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('admin.kyc.personal_info_title') }}</h3></div>
    <div class="card-pad">
        <table class="tbl">
            <tbody>
                @foreach($rows as $key => $value)
                <tr>
                    <td style="width:240px;color:var(--ink-muted)">{{ __($key) }}</td>
                    <td>{{ $value ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Decision --}}
@if($user->kyc_status === \App\Enums\KycStatus::UNDER_REVIEW)
<div class="card">
    <div class="card-h"><h3>{{ __('admin.kyc.decision_title') }}</h3></div>
    <div class="card-pad" style="display:flex;flex-direction:column;gap:18px">
        {{-- Approve --}}
        <form method="POST" action="{{ route('admin.kyc.approve', $user) }}" data-confirm="{{ __('admin.kyc.confirm_approve') }}" data-confirm-label="{{ __('admin.kyc.approve') }}">
            @csrf
            <button type="submit" class="btn" style="background:#10b981;color:#fff">{{ __('admin.kyc.approve') }}</button>
        </form>
        {{-- Reject --}}
        <form method="POST" action="{{ route('admin.kyc.reject', $user) }}">
            @csrf
            <div class="field" style="margin-bottom:10px">
                <label>{{ __('admin.kyc.reject_reason_label') }} <span class="req">*</span></label>
                <input type="text" name="reason" class="input" placeholder="{{ __('admin.kyc.reject_reason_placeholder') }}" required>
            </div>
            <button type="submit" class="btn" style="background:#ef4444;color:#fff">{{ __('admin.kyc.reject') }}</button>
        </form>
    </div>
</div>
@endif

@endsection
