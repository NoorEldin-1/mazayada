@extends('layouts.citizen')
@section('title', __('dashboard.nav_identity'))
@section('content')

@php
    $status = $user->kyc_status;
    $isUnderReview = $status === \App\Enums\KycStatus::UNDER_REVIEW;
    $isComplete = $status === \App\Enums\KycStatus::COMPLETE;
    $isRejected = $status === \App\Enums\KycStatus::REJECTED;
    $isSuspended = $status === \App\Enums\KycStatus::SUSPENDED;
    $bio = $user->biometrics;
    $canSubmit = $user->kycCanSubmit();          // PENDING or REJECTED → editable
    $hasAllDocs = $user->hasAllKycDocuments();
    $selectedWilaya = $user->commune?->wilaya_id;
    $selectedCommune = $user->commune_id;
    $docs = ['id-front' => __('kyc.doc_id_front'), 'id-back' => __('kyc.doc_id_back'), 'selfie-with-id' => __('kyc.doc_selfie')];
    $s1done = $hasAllDocs;
    $s2done = $isUnderReview || $isComplete;
    $s3done = $isComplete;
@endphp

<div style="margin-bottom:24px">
    <h2 style="font-size:24px;font-weight:700;margin:0 0 8px">{{ __('kyc.page_title') }}</h2>
    <p style="color:var(--muted);font-size:14px;margin:0">{{ __('kyc.page_subtitle') }}</p>
</div>

{{-- Status banner --}}
@if($isUnderReview)
<div style="background:#E0EBF7;color:#27568A;padding:16px 20px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;gap:12px;align-items:center">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <div>
        <strong>{{ __('kyc.banner_under_review_title') }}</strong>
        <div style="margin-top:2px">{{ __('kyc.banner_under_review_text', ['date' => $user->kyc_submitted_at?->format('Y-m-d H:i')]) }}</div>
    </div>
</div>
@elseif($isComplete)
<div style="background:#E5F3EC;color:#1d6045;padding:16px 20px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;gap:12px;align-items:center">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div><strong>{{ __('kyc.banner_complete_title') }}</strong>
        <div style="margin-top:2px">{{ __('kyc.banner_complete_text') }}</div></div>
</div>
@elseif($isRejected)
<div style="background:#FBE2E0;color:#8E2F2A;padding:16px 20px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;gap:12px;align-items:flex-start">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <strong>{{ __('kyc.banner_rejected_title') }}</strong>
        @if($user->kyc_rejection_reason)
            <div style="margin-top:4px">{{ __('kyc.banner_rejected_reason') }} <strong>{{ $user->kyc_rejection_reason }}</strong></div>
        @endif
        <div style="margin-top:4px">{{ __('kyc.banner_rejected_hint') }}</div>
    </div>
</div>
@elseif($isSuspended)
<div style="background:#FBE2E0;color:#8E2F2A;padding:16px 20px;border-radius:14px;margin-bottom:20px;font-size:14px;display:flex;gap:12px;align-items:center">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
    <div><strong>{{ __('kyc.banner_suspended_title') }}</strong>
        <div style="margin-top:2px">{{ __('kyc.banner_suspended_text') }}</div></div>
</div>
@endif

{{-- Step indicator --}}
<div class="steps-h">
    <div class="si {{ $s1done ? 'done' : ($canSubmit ? 'cur' : '') }}">
        <div class="n">1</div>
        <div class="tx"><div class="l">{{ __('kyc.step1_label') }}</div><div class="t">{{ __('kyc.step1_title') }}</div></div>
    </div>
    <div class="ln {{ $s1done ? 'done' : '' }}"></div>
    <div class="si {{ $s2done ? 'done' : ($s1done ? 'cur' : '') }}">
        <div class="n">2</div>
        <div class="tx"><div class="l">{{ __('kyc.step2_label') }}</div><div class="t">{{ __('kyc.step2_title') }}</div></div>
    </div>
    <div class="ln {{ $s2done ? 'done' : '' }}"></div>
    <div class="si {{ $s3done ? 'done' : ($isUnderReview ? 'cur' : '') }}">
        <div class="n">3</div>
        <div class="tx"><div class="l">{{ __('kyc.step3_label') }}</div><div class="t">{{ __('kyc.step3_title') }}</div></div>
    </div>
</div>

@if($errors->any())
<div style="background:#FBE2E0;color:#8E2F2A;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">
    {{ session('success') }}
</div>
@endif

{{-- Step 1: Document Upload --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('kyc.step1_title') }}</h3></div>
    <div class="card-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
            @foreach($docs as $type => $label)
            @php $field = str_replace('-', '_', $type) . '_path'; $uploaded = $bio && $bio->$field; @endphp
            <div>
                <form action="{{ route('citizen.kyc.upload', $type) }}" method="POST" enctype="multipart/form-data" id="form-{{ $type }}">
                    @csrf
                    <div class="upbox {{ $uploaded ? 'done' : '' }}" @if($canSubmit) onclick="this.querySelector('input[type=file]')?.click()" style="cursor:pointer" @endif>
                        @if($uploaded)
                            <img src="{{ route('citizen.kyc.document', $type) }}" alt="{{ $label }}" style="width:100%;max-height:96px;object-fit:cover;border-radius:10px;margin-bottom:8px">
                            <div class="t">{{ $label }}</div>
                            <div class="s">{{ $canSubmit ? __('kyc.uploaded_replace') : __('kyc.uploaded') }}</div>
                        @else
                            <div class="ic">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </div>
                            <div class="t">{{ $label }}</div>
                            <div class="s">{{ __('kyc.upload_hint') }}</div>
                        @endif
                        @if($canSubmit)
                            <input type="file" name="file" accept="image/jpeg,image/png" style="display:none" onchange="this.form.submit()">
                        @endif
                    </div>
                </form>
            </div>
            @endforeach
        </div>
        <div style="background:#FBEFD6;border-radius:14px;padding:18px;margin-top:18px;font-size:13px;color:#8A6310">
            <strong>{{ __('kyc.requirements_title') }}</strong>
            <ul style="margin:8px 0 0;padding-inline-start:18px;line-height:2">
                <li>{{ __('kyc.req_clear') }}</li>
                <li>{{ __('kyc.req_readable') }}</li>
                <li>{{ __('kyc.req_size') }}</li>
                <li>{{ __('kyc.req_formats') }}</li>
            </ul>
        </div>
    </div>
</div>

{{-- Step 2: Personal Info + Step 3: Submit --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-h"><h3>{{ __('kyc.step2_title') }}</h3></div>
    <div class="card-pad">
        <form action="{{ route('citizen.kyc.submit') }}" method="POST">
            @csrf
            @php $ro = $canSubmit ? '' : 'disabled'; @endphp
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>{{ __('kyc.f_first_name_fr') }} <span class="req">*</span></label>
                    <input class="input" name="first_name_fr" value="{{ old('first_name_fr', $user->first_name_fr) }}" dir="ltr" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_last_name_fr') }} <span class="req">*</span></label>
                    <input class="input" name="last_name_fr" value="{{ old('last_name_fr', $user->last_name_fr) }}" dir="ltr" {{ $ro }} required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>{{ __('kyc.f_father_name') }} <span class="req">*</span></label>
                    <input class="input" name="father_name" value="{{ old('father_name', $user->father_name) }}" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_mother_fullname') }} <span class="req">*</span></label>
                    <input class="input" name="mother_fullname" value="{{ old('mother_fullname', $user->mother_fullname) }}" {{ $ro }} required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>{{ __('kyc.f_wilaya') }} <span class="req">*</span></label>
                    <select class="input" name="wilaya_id" id="wilaya-select" {{ $ro }} required>
                        <option value="">{{ __('kyc.choose_wilaya') }}</option>
                        @foreach(\App\Models\Wilaya::orderBy('code')->get() as $w)
                            <option value="{{ $w->id }}" @selected(old('wilaya_id', $selectedWilaya) == $w->id)>{{ $w->code }} — {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_commune') }} <span class="req">*</span></label>
                    <select class="input" name="commune_id" id="commune-select" {{ $ro }} required data-selected="{{ old('commune_id', $selectedCommune) }}">
                        <option value="">{{ __('kyc.choose_wilaya_first') }}</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin-bottom:16px">
                <label>{{ __('kyc.f_full_address') }} <span class="req">*</span></label>
                <input class="input" name="address" value="{{ old('address', $user->address) }}" {{ $ro }} required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="field">
                    <label>{{ __('kyc.f_postal_code') }} <span class="req">*</span></label>
                    <input class="input" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" dir="ltr" maxlength="5" pattern="\d{5}" inputmode="numeric" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_profession') }}</label>
                    <input class="input" name="profession" value="{{ old('profession', $user->profession) }}" {{ $ro }}>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
                <div class="field">
                    <label>{{ __('kyc.f_expected_income') }}</label>
                    <input class="input" type="number" name="expected_income" value="{{ old('expected_income', $user->expected_income) }}" dir="ltr" min="0" {{ $ro }}>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_rip') }}</label>
                    <input class="input" name="rip" value="{{ old('rip', $user->rip) }}" dir="ltr" {{ $ro }}>
                </div>
            </div>

            {{-- Step 3: Review & submit --}}
            <div style="border-top:1px solid var(--line);padding-top:20px">
                <h3 style="font-size:16px;font-weight:700;margin:0 0 6px">{{ __('kyc.step3_title') }}</h3>
                <p style="color:var(--muted);font-size:13px;margin:0 0 16px">{{ __('kyc.step3_hint') }}</p>
                @if($canSubmit)
                    @unless($hasAllDocs)
                        <div style="background:#FBEFD6;color:#8A6310;padding:12px 16px;border-radius:10px;margin-bottom:14px;font-size:13px">
                            {{ __('kyc.error_docs_required') }}
                        </div>
                    @endunless
                    <button type="submit" class="btn btn-primary btn-block btn-lg" {{ $hasAllDocs ? '' : 'disabled' }}>{{ __('kyc.submit') }}</button>
                @elseif($isUnderReview)
                    <div style="background:#E0EBF7;color:#27568A;padding:14px 18px;border-radius:10px;font-size:13px;text-align:center">{{ __('kyc.flash_under_review') }}</div>
                @elseif($isComplete)
                    <div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:10px;font-size:13px;text-align:center">{{ __('kyc.banner_complete_text') }}</div>
                @endif
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const communeSelect = document.getElementById('commune-select');
const wilayaSelect = document.getElementById('wilaya-select');

async function loadCommunes(wilayaId, preselect) {
    if (!wilayaId) { communeSelect.innerHTML = '<option value="">{{ __('kyc.choose_wilaya_first') }}</option>'; return; }
    communeSelect.innerHTML = '<option value="">{{ __('common.loading') }}</option>';
    try {
        const res = await fetch('/api/v1/wilayas/' + wilayaId + '/communes');
        const data = await res.json();
        const f = @json(app()->getLocale() === 'fr' ? 'name_fr' : 'name_ar');
        communeSelect.innerHTML = '<option value="">{{ __('kyc.choose_commune') }}</option>' +
            data.map(c => `<option value="${c.id}" ${String(c.id) === String(preselect) ? 'selected' : ''}>${c[f] || c.name_ar}</option>`).join('');
    } catch(e) { communeSelect.innerHTML = '<option value="">{{ __('kyc.js_load_error') }}</option>'; }
}

wilayaSelect?.addEventListener('change', function() { loadCommunes(this.value, null); });

// Pre-load the saved wilaya's communes on page load so a returning/rejected
// user sees their previous commune selected.
@if(old('wilaya_id', $selectedWilaya))
    loadCommunes(@json((string) old('wilaya_id', $selectedWilaya)), communeSelect.dataset.selected);
@endif
</script>
@endpush

@endsection
