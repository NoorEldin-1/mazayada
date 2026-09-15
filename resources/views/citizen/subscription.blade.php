@extends('layouts.citizen')
@section('title', __('subscriptions.page_title'))
@section('content')

@php
    use App\Enums\SubscriptionStatus;
    $s = $subscription;
    $isActive = $s && $s->isActive();
@endphp

<div class="flex flex-col gap-6">

    {{-- Current subscription (edit 25) --}}
    <x-ui.card :title="__('subscriptions.current_title')">
        @if($s)
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span class="chip {{ $s->status->chipClass() }}"><span class="dot"></span>{{ $s->status->label() }}</span>
                @if($isPremium)
                    <strong class="text-primary">{{ __('subscriptions.premium_active') }}</strong>
                @endif
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:1rem">
                <div><div class="text-xs text-muted mb-1">{{ __('subscriptions.plan') }}</div><div class="font-semibold">{{ $s->plan?->name }}</div></div>
                <div><div class="text-xs text-muted mb-1">{{ __('subscriptions.started_at') }}</div><div class="num">{{ $s->started_at?->format('Y-m-d') ?? '—' }}</div></div>
                <div><div class="text-xs text-muted mb-1">{{ __('subscriptions.expires_at') }}</div><div class="num">{{ $s->expires_at?->format('Y-m-d') ?? '—' }}</div></div>
                <div><div class="text-xs text-muted mb-1">{{ __('subscriptions.days_remaining') }}</div><div class="num">{{ $s->daysRemaining() }}</div></div>
                <div><div class="text-xs text-muted mb-1">{{ __('subscriptions.auto_renew') }}</div><div>{{ $s->auto_renew ? __('subscriptions.auto_renew_on') : __('subscriptions.auto_renew_off') }}</div></div>
            </div>
            @if($isActive && $s->auto_renew)
                <form method="POST" action="{{ route('citizen.subscription.cancel-auto-renew') }}" class="mt-4"
                      data-confirm="{{ __('subscriptions.confirm_stop_auto_renew') }}" data-confirm-label="{{ __('subscriptions.stop_auto_renew') }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.btn variant="danger-ghost" size="sm">{{ __('subscriptions.stop_auto_renew') }}</x-ui.btn>
                </form>
            @endif
            <p class="text-xs text-muted mt-4">{{ __('subscriptions.renew_note') }}</p>
        @else
            <p class="text-muted">{{ __('subscriptions.not_subscribed') }}</p>
        @endif
    </x-ui.card>

    {{-- Plans (edit 24) --}}
    <x-ui.card :title="__('subscriptions.plans_title')">
        @if($plans->isEmpty())
            <p class="text-muted">{{ __('subscriptions.no_plans') }}</p>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:1rem">
                @foreach($plans as $plan)
                    <div class="border rounded-xl p-4 flex flex-col gap-2 {{ $plan->is_recommended ? 'border-primary' : 'border-line' }}">
                        <div class="flex items-center justify-between gap-2">
                            <strong>{{ $plan->name }}</strong>
                            @if($plan->is_recommended)<span class="chip chip-info">{{ __('subscriptions.recommended') }}</span>@endif
                        </div>
                        <div class="text-2xl font-bold text-primary"><x-money :centimes="$plan->price" /> <span class="text-xs text-muted font-normal">/ {{ $plan->period->label() }}</span></div>
                        @if($plan->description)<p class="text-sm text-muted">{{ $plan->description }}</p>@endif
                        @if($features = $plan->localizedFeatures())
                            <ul class="text-sm flex flex-col gap-1" style="list-style:disc;padding-inline-start:1.1rem">
                                @foreach($features as $feature)<li>{{ $feature }}</li>@endforeach
                            </ul>
                        @endif
                        <form method="POST" action="{{ route('citizen.subscription.store') }}" class="mt-auto pt-2">
                            @csrf
                            <input type="hidden" name="plan_code" value="{{ $plan->code }}">
                            <x-ui.btn :variant="$plan->is_recommended ? 'primary' : 'outline'" class="w-full">{{ $isPremium ? __('subscriptions.renew') : __('subscriptions.subscribe') }}</x-ui.btn>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- Notification preferences (edits 27-30) --}}
    <x-ui.card :title="__('subscriptions.prefs_title')">
        <p class="text-sm text-muted mb-4">{{ __('subscriptions.prefs_intro') }}</p>
        <form method="POST" action="{{ route('citizen.subscription.preferences') }}">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-3 mb-5">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="new_auction_alerts" value="1" @checked($prefs['new_auction_alerts'])>
                    <span class="font-semibold">{{ __('subscriptions.new_auction_alerts') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="push" value="1" @checked($prefs['channels']['push'])>
                    {{ __('subscriptions.ch_push') }}
                </label>
                @php $emailLocked = $prefs['email_requires_premium'] && ! $prefs['is_premium']; @endphp
                <label class="flex items-center gap-2 {{ $emailLocked ? 'opacity-60' : 'cursor-pointer' }}">
                    <input type="checkbox" name="email" value="1" @checked($prefs['channels']['email']) @disabled($emailLocked)>
                    {{ __('subscriptions.ch_email') }}
                    @if($emailLocked)<small class="text-muted">— {{ __('subscriptions.email_premium_only') }}</small>@endif
                </label>
                <label class="flex items-center gap-2 {{ $prefs['sms_available'] ? 'cursor-pointer' : 'opacity-60' }}">
                    <input type="checkbox" name="sms" value="1" @checked($prefs['channels']['sms']) @disabled(! $prefs['sms_available'])>
                    {{ __('subscriptions.ch_sms') }}
                    @unless($prefs['sms_available'])<small class="text-muted">— {{ __('subscriptions.sms_unavailable') }}</small>@endunless
                </label>
            </div>

            <div class="font-semibold mb-1">{{ __('subscriptions.categories') }}</div>
            <p class="text-xs text-muted mb-3">{{ __('subscriptions.categories_hint') }}</p>
            <div class="flex flex-wrap gap-3 mb-5">
                @foreach($prefs['available_categories'] as $cat)
                    <label class="flex items-center gap-2 cursor-pointer border border-line rounded-lg px-3 py-1.5">
                        <input type="checkbox" name="auction_categories[]" value="{{ $cat['id'] }}" @checked(in_array($cat['id'], $prefs['auction_categories'], true))>
                        {{ $cat['name'] }}
                    </label>
                @endforeach
            </div>

            <p class="text-xs text-muted mb-4">{{ __('subscriptions.premium_first_note') }}</p>
            <x-ui.btn variant="primary">{{ __('subscriptions.save_prefs') }}</x-ui.btn>
        </form>
    </x-ui.card>
</div>

@endsection
