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

<x-ui.page-header :title="__('kyc.page_title')" :subtitle="__('kyc.page_subtitle')" />

{{-- Status banner --}}
@if($isUnderReview)
<div class="bg-info/10 text-info flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <div>
        <strong>{{ __('kyc.banner_under_review_title') }}</strong>
        <div style="margin-top:2px">{{ __('kyc.banner_under_review_text', ['date' => $user->kyc_submitted_at?->format('Y-m-d H:i')]) }}</div>
    </div>
</div>
@elseif($isComplete)
<div class="bg-ok/10 text-ok flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div><strong>{{ __('kyc.banner_complete_title') }}</strong>
        <div style="margin-top:2px">{{ __('kyc.banner_complete_text') }}</div></div>
</div>
@elseif($isRejected)
<div class="bg-danger/10 text-danger flex gap-3 items-start rounded-2xl px-5 py-4 mb-5 text-sm">
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
<div class="bg-danger/10 text-danger flex gap-3 items-center rounded-2xl px-5 py-4 mb-5 text-sm">
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
<div class="mb-5 rounded-xl px-4 py-3 text-sm bg-danger/10 text-danger flex items-center gap-2">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div class="mb-5 rounded-xl px-4 py-3 text-sm bg-ok/10 text-ok">
    {{ session('success') }}
</div>
@endif

{{-- Step 1: Document Upload --}}
<x-ui.card :title="__('kyc.step1_title')" class="mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

        {{-- Optional standalone biometric photo (spec §3.2) — separate cap (120KB). --}}
        @php $bioPhotoUploaded = $bio && $bio->photo_biometric_path; @endphp
        <div style="margin-top:16px;max-width:240px">
            <form action="{{ route('citizen.kyc.upload', 'photo-biometric') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="upbox {{ $bioPhotoUploaded ? 'done' : '' }}" @if($canSubmit) onclick="this.querySelector('input[type=file]')?.click()" style="cursor:pointer" @endif>
                    @if($bioPhotoUploaded)
                        <img src="{{ route('citizen.kyc.document', 'photo-biometric') }}" alt="{{ __('kyc.doc_photo_biometric') }}" style="width:100%;max-height:96px;object-fit:cover;border-radius:10px;margin-bottom:8px">
                        <div class="t">{{ __('kyc.doc_photo_biometric') }}</div>
                        <div class="s">{{ $canSubmit ? __('kyc.uploaded_replace') : __('kyc.uploaded') }}</div>
                    @else
                        <div class="ic">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="t">{{ __('kyc.doc_photo_biometric') }}</div>
                        <div class="s">{{ __('kyc.doc_photo_biometric_hint') }}</div>
                    @endif
                    @if($canSubmit)
                        <input type="file" name="file" accept="image/jpeg,image/png" style="display:none" onchange="this.form.submit()">
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-accent-soft text-accent-2 rounded-2xl p-4 mt-4 text-sm">
            <strong>{{ __('kyc.requirements_title') }}</strong>
            <ul style="margin:8px 0 0;padding-inline-start:18px;line-height:2">
                <li>{{ __('kyc.req_clear') }}</li>
                <li>{{ __('kyc.req_readable') }}</li>
                <li>{{ __('kyc.req_size') }}</li>
                <li>{{ __('kyc.req_formats') }}</li>
            </ul>
        </div>
</x-ui.card>

{{-- Step 2: Personal Info + Step 3: Submit --}}
<x-ui.card :title="__('kyc.step2_title')" class="mb-5">
        <form action="{{ route('citizen.kyc.submit') }}" method="POST">
            @csrf
            @php $ro = $canSubmit ? '' : 'disabled'; @endphp
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div class="field">
                    <label>{{ __('kyc.f_first_name_fr') }} <span class="req">*</span></label>
                    <input class="input" name="first_name_fr" value="{{ old('first_name_fr', $user->first_name_fr) }}" dir="ltr" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_last_name_fr') }} <span class="req">*</span></label>
                    <input class="input" name="last_name_fr" value="{{ old('last_name_fr', $user->last_name_fr) }}" dir="ltr" {{ $ro }} required>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div class="field">
                    <label>{{ __('kyc.f_father_name') }} <span class="req">*</span></label>
                    <input class="input" name="father_name" value="{{ old('father_name', $user->father_name) }}" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_mother_fullname') }} <span class="req">*</span></label>
                    <input class="input" name="mother_fullname" value="{{ old('mother_fullname', $user->mother_fullname) }}" {{ $ro }} required>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
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
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div class="field">
                    <label>{{ __('kyc.f_postal_code') }} <span class="req">*</span></label>
                    <input class="input" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" dir="ltr" maxlength="5" pattern="\d{5}" inputmode="numeric" {{ $ro }} required>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_profession') }}</label>
                    <input class="input" name="profession" value="{{ old('profession', $user->profession) }}" {{ $ro }}>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4 mb-6">
                <div class="field">
                    <label>{{ __('kyc.f_expected_income') }}</label>
                    <input class="input" type="number" name="expected_income" value="{{ old('expected_income', $user->expected_income) }}" dir="ltr" min="0" {{ $ro }}>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_rip') }}</label>
                    <input class="input" name="rip" value="{{ old('rip', $user->rip) }}" dir="ltr" placeholder="00799999000123456789" {{ $ro }}>
                </div>
            </div>

            {{-- Identity document (spec §3.2) --}}
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div class="field">
                    <label>{{ __('kyc.f_id_type') }}</label>
                    <select class="input" name="id_type" {{ $ro }}>
                        <option value="">{{ __('kyc.id_type_none') }}</option>
                        @foreach(\App\Enums\IdDocumentType::cases() as $idt)
                            <option value="{{ $idt->value }}" @selected(old('id_type') === $idt->value)>{{ $idt->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_id_number') }}</label>
                    <input class="input" name="id_number" value="{{ old('id_number') }}" dir="ltr" {{ $ro }}>
                </div>
            </div>

            {{-- Tax / statistical IDs — optional, for merchants & companies (spec §3.2) --}}
            <div class="grid sm:grid-cols-2 gap-4 mb-6">
                <div class="field">
                    <label>{{ __('kyc.f_nif') }}</label>
                    <input class="input" name="nif" value="{{ old('nif', $user->nif) }}" dir="ltr" maxlength="15" inputmode="numeric" {{ $ro }}>
                </div>
                <div class="field">
                    <label>{{ __('kyc.f_nis') }}</label>
                    <input class="input" name="nis" value="{{ old('nis', $user->nis) }}" dir="ltr" maxlength="18" inputmode="numeric" {{ $ro }}>
                </div>
            </div>

            {{-- Step 3: Review & submit --}}
            <div class="border-t border-line pt-5">
                <h3 class="text-base font-bold text-ink mb-1.5">{{ __('kyc.step3_title') }}</h3>
                <p class="text-sm text-muted mb-4">{{ __('kyc.step3_hint') }}</p>
                @if($canSubmit)
                    @unless($hasAllDocs)
                        <div class="bg-accent-soft text-accent-2 rounded-2xl p-4 mt-4 text-sm">
                            {{ __('kyc.error_docs_required') }}
                        </div>
                    @endunless
                    <x-ui.btn variant="primary" size="lg" class="w-full" :disabled="! $hasAllDocs">{{ __('kyc.submit') }}</x-ui.btn>
                @elseif($isUnderReview)
                    <div class="bg-info/10 text-info rounded-xl px-4 py-3.5 text-sm text-center">{{ __('kyc.flash_under_review') }}</div>
                @elseif($isComplete)
                    <div class="bg-ok/10 text-ok rounded-xl px-4 py-3.5 text-sm text-center">{{ __('kyc.banner_complete_text') }}</div>
                @endif
            </div>
        </form>
</x-ui.card>

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
