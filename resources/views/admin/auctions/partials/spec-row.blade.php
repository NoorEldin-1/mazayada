{{-- One repeatable asset-specification row.
     $index : int for a server-rendered row, or the literal __INDEX__ placeholder
              for the <template> clone (the JS swaps it for a running counter).
     $spec  : the row's old()/stored values (may be empty for a fresh row). --}}
@php($spec = $spec ?? [])
<div class="spec-row" style="border:1px solid var(--line);border-radius:10px;padding:1rem;margin-block-end:0.75rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-block-end:0.75rem">
        <span style="font-size:0.85rem;font-weight:600;color:var(--ink-muted)">{{ __('admin.auctions.spec_row_label') }}</span>
        <button type="button" class="spec-remove" style="background:none;border:0;color:var(--danger,#D9544E);font-size:0.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            {{ __('admin.auctions.spec_remove') }}
        </button>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="field">
            <label>{{ __('admin.auctions.f_spec_title_ar') }} <span class="text-danger">*</span></label>
            <input type="text" name="specifications[{{ $index }}][title_ar]" class="input" value="{{ $spec['title_ar'] ?? '' }}" maxlength="150">
            @error('specifications.'.$index.'.title_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label>{{ __('admin.auctions.f_spec_title_fr') }}</label>
            <input type="text" name="specifications[{{ $index }}][title_fr]" class="input" value="{{ $spec['title_fr'] ?? '' }}" maxlength="150">
            @error('specifications.'.$index.'.title_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-block-start:0.5rem">
        <div class="field">
            <label>{{ __('admin.auctions.f_spec_body_ar') }} <span class="text-danger">*</span></label>
            <textarea name="specifications[{{ $index }}][body_ar]" class="textarea" rows="3" maxlength="2000">{{ $spec['body_ar'] ?? '' }}</textarea>
            @error('specifications.'.$index.'.body_ar') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label>{{ __('admin.auctions.f_spec_body_fr') }}</label>
            <textarea name="specifications[{{ $index }}][body_fr]" class="textarea" rows="3" maxlength="2000">{{ $spec['body_fr'] ?? '' }}</textarea>
            @error('specifications.'.$index.'.body_fr') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        </div>
    </div>
</div>
