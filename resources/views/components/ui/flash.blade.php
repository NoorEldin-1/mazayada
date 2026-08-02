{{--
    Single feedback surface for every admin/dashboard page: success + error
    flashes and the consolidated validation summary.

    Rendered once by layouts.admin, so no page needs its own copy — before this
    existed, most modules returned back()->with('success') or a redirect with
    validation errors into a layout that displayed neither, which made saves and
    rejected submissions look like "nothing happened".
--}}

@if(session('success'))
    <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-ok/10 text-ok px-4 py-3 text-sm" role="status">
        <svg class="size-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm" role="alert">
        <svg class="size-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if($errors->any())
    {{-- One consolidated list: on long forms the user sees every missing field
         at once instead of being walked through them one submit at a time. --}}
    <div class="mb-5 rounded-xl bg-danger/10 text-danger px-4 py-3 text-sm" role="alert" data-error-summary tabindex="-1">
        <div class="flex items-start gap-2.5">
            <svg class="size-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div class="min-w-0">
                <p class="font-semibold mb-1">{{ __('common.validation_summary') }}</p>
                <ul class="list-disc list-inside space-y-0.5 opacity-90">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
