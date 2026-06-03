@extends('layouts.admin')

@section('title', __('admin.users.view_title'))
@section('page-title', __('admin.users.view_title'))

@section('content')

@php
    $bio = $user->biometrics;
    $docs = ['id-front' => __('kyc.doc_id_front'), 'id-back' => __('kyc.doc_id_back'), 'selfie-with-id' => __('kyc.doc_selfie')];
    $income = $user->expected_income ? number_format($user->expected_income, 0, ',', ' ').' '.__('common.currency') : null;
    $identity = [
        'admin.kyc.f_name_ar' => $user->fullNameAr(),
        'kyc.f_first_name_fr' => $user->fullNameFr() ?: null,
        'admin.th_email' => $user->email,
        'admin.users.f_phone' => $user->phone,
        'admin.kyc.f_birth_date' => $user->birth_date?->format('Y-m-d'),
        'kyc.f_father_name' => $user->father_name,
        'kyc.f_mother_fullname' => $user->mother_fullname,
    ];
    $location = [
        'kyc.f_wilaya' => $user->commune?->wilaya?->name,
        'kyc.f_commune' => $user->commune?->name,
        'kyc.f_full_address' => $user->address,
        'kyc.f_postal_code' => $user->postal_code,
    ];
    $other = [
        'kyc.f_profession' => $user->profession,
        'kyc.f_expected_income' => $income,
        'admin.users.f_nif' => $user->nif,
        'admin.users.f_nis' => $user->nis,
        'kyc.f_rip' => $user->rip,
        'admin.users.f_premium' => $user->premium_until?->format('Y-m-d'),
        'admin.users.f_registered' => $user->created_at?->format('Y-m-d'),
    ];
@endphp

<div style="margin-bottom:18px">
    <a href="{{ route('admin.users.index') }}" style="font-size:13px;color:var(--ink-muted);text-decoration:none">← {{ __('admin.users.back_to_list') }}</a>
</div>

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">{{ session('success') }}</div>
@endif
@if($errors->any())
<div style="background:#FBE2E0;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">{{ $errors->first() }}</div>
@endif

{{-- Header --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>{{ $user->fullNameAr() }}</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <span class="chip chip-info">{{ $user->role->label() }}</span>
            <span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span>
            @if($user->is_blacklisted)
                <span class="chip chip-danger">{{ __('admin.users.blacklisted') }}</span>
            @else
                <span class="chip {{ $user->account_status->chipClass() }}">{{ $user->account_status->label() }}</span>
            @endif
        </div>
    </div>
    <div class="card-pad" style="display:flex;gap:24px;flex-wrap:wrap;font-size:13px;color:var(--ink-muted)">
        <span>NIN: <strong style="color:var(--ink);direction:ltr">{{ $user->nin }}</strong></span>
        <span>{{ __('admin.users.stat_participations') }}: <strong style="color:var(--ink)">{{ $user->participations_count }}</strong></span>
        <span>{{ __('admin.users.stat_bids') }}: <strong style="color:var(--ink)">{{ $user->bids_count }}</strong></span>
        <span>{{ __('admin.users.stat_won') }}: <strong style="color:var(--ink)">{{ $user->won_auctions_count }}</strong></span>
    </div>
</div>

{{-- Identity & contact --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('admin.users.sec_identity') }}</h3></div>
    <div class="card-pad">
        <table class="tbl"><tbody>
            @foreach($identity as $key => $value)
            <tr><td style="width:240px;color:var(--ink-muted)">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody></table>
    </div>
</div>

{{-- KYC --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h" style="display:flex;align-items:center;justify-content:space-between">
        <h3>{{ __('admin.users.sec_kyc') }}</h3>
        @if($user->kyc_status === \App\Enums\KycStatus::UNDER_REVIEW)
            <a href="{{ route('admin.kyc.show', $user) }}" class="btn btn-sm" style="background:#15573f;color:#fff">{{ __('admin.users.view_kyc_review') }}</a>
        @endif
    </div>
    <div class="card-pad">
        <table class="tbl"><tbody>
            <tr><td style="width:240px;color:var(--ink-muted)">{{ __('admin.th_kyc') }}</td><td><span class="chip {{ $user->kyc_status->chipClass() }}">{{ $user->kyc_status->label() }}</span></td></tr>
            <tr><td style="color:var(--ink-muted)">{{ __('admin.kyc.th_submitted_date') }}</td><td>{{ $user->kyc_submitted_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
            <tr><td style="color:var(--ink-muted)">{{ __('admin.users.f_kyc_completed') }}</td><td>{{ $user->kyc_completed_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
            @if($user->kyc_rejection_reason)
            <tr><td style="color:var(--ink-muted)">{{ __('admin.users.f_rejection_reason') }}</td><td>{{ $user->kyc_rejection_reason }}</td></tr>
            @endif
        </tbody></table>

        {{-- Documents --}}
        @if($bio && ($bio->id_front_path || $bio->id_back_path || $bio->selfie_with_id_path))
        <div style="margin-top:18px">
            <div style="font-size:13px;font-weight:600;margin-bottom:10px">{{ __('admin.kyc.documents_title') }}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                @foreach($docs as $type => $label)
                @php $field = str_replace('-', '_', $type) . '_path'; $uploaded = $bio && $bio->$field; @endphp
                <div style="border:1px solid var(--line);border-radius:12px;overflow:hidden">
                    <div style="padding:10px 14px;font-size:13px;font-weight:600;background:#f8faf9">{{ $label }}</div>
                    @if($uploaded)
                        <a href="{{ route('admin.kyc.document', [$user, $type]) }}" target="_blank">
                            <img src="{{ route('admin.kyc.document', [$user, $type]) }}" alt="{{ $label }}" style="width:100%;display:block;max-height:200px;object-fit:cover">
                        </a>
                    @else
                        <div style="padding:32px;text-align:center;color:var(--ink-muted);font-size:13px">{{ __('admin.kyc.no_document') }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Location --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('admin.users.sec_location') }}</h3></div>
    <div class="card-pad">
        <table class="tbl"><tbody>
            @foreach($location as $key => $value)
            <tr><td style="width:240px;color:var(--ink-muted)">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody></table>
    </div>
</div>

{{-- Other --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('admin.users.sec_other') }}</h3></div>
    <div class="card-pad">
        <table class="tbl"><tbody>
            @foreach($other as $key => $value)
            <tr><td style="width:240px;color:var(--ink-muted)">{{ __($key) }}</td><td>{{ $value ?: '—' }}</td></tr>
            @endforeach
        </tbody></table>
    </div>
</div>

{{-- Account action --}}
<div class="card">
    <div class="card-h"><h3>{{ __('admin.users.th_account_status') }}</h3></div>
    <div class="card-pad">
        @if($user->is_blacklisted)
            <div style="background:#FBE2E0;color:#8E2F2A;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:14px">
                <strong>{{ __('admin.users.blacklisted') }}:</strong> {{ $user->blacklist_reason }}
            </div>
            <form method="POST" action="{{ route('admin.users.unblacklist', $user) }}"
                  data-confirm="{{ __('admin.users.confirm_unblacklist_prompt') }}" data-confirm-label="{{ __('admin.users.unblacklist_action') }}">
                @csrf
                <button type="submit" class="btn" style="background:#10b981;color:#fff">{{ __('admin.users.unblacklist_action') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.blacklist', $user) }}"
                  data-confirm="{{ __('admin.users.confirm_blacklist_prompt') }}" data-confirm-variant="danger" data-confirm-label="{{ __('admin.users.confirm_blacklist') }}">
                @csrf
                <div class="field" style="margin-bottom:10px;max-width:420px">
                    <label>{{ __('admin.users.blacklist_reason_label') }} <span class="req">*</span></label>
                    <input type="text" name="reason" class="input" placeholder="{{ __('admin.users.blacklist_reason_placeholder') }}" required>
                </div>
                <button type="submit" class="btn" style="background:var(--red-600,#ef4444);color:#fff">{{ __('admin.users.confirm_blacklist') }}</button>
            </form>
        @endif
    </div>
</div>

@endsection
