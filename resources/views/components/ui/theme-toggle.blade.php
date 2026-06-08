{{--
    Light/dark theme toggle.
    Initial state is server-rendered on <html data-theme> (read from the
    `theme` cookie in the layout), so there is no flash. The click is handled
    by resources/js/dashboard.js via the [data-theme-toggle] hook.
--}}
<button
    type="button"
    data-theme-toggle
    aria-label="{{ __('common.toggle_theme') }}"
    title="{{ __('common.toggle_theme') }}"
    {{ $attributes->merge(['class' => 'inline-grid place-items-center size-9 rounded-lg text-ink-2 bg-bg border border-line hover:bg-bg-2 hover:text-ink transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40']) }}
>
    {{-- Sun: shown in dark mode (click → switch to light) --}}
    <svg class="size-[18px] hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
    </svg>
    {{-- Moon: shown in light mode (click → switch to dark) --}}
    <svg class="size-[18px] block dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
</button>
