{{--
    Paginated transactions table for the Financial Reports module.

    Expects: $transactions (paginator of Payment), $showUser (bool).
--}}
<x-ui.card :padding="false" class="mb-5">
    <x-slot:header>
        <h3 class="text-base font-semibold text-ink">{{ __('reports.section_transactions') }}</h3>
        <span class="ms-auto text-xs text-muted">{{ __('reports.showing_count', ['count' => number_format($transactions->total())]) }}</span>
    </x-slot:header>

    <div class="overflow-x-auto">
        <table class="ui-table" style="min-width:{{ $showUser ? '760px' : '620px' }}">
            <thead>
                <tr>
                    <th>{{ __('reports.th_date') }}</th>
                    <th>{{ __('reports.th_auction') }}</th>
                    @if($showUser)<th>{{ __('reports.th_user') }}</th>@endif
                    <th>{{ __('reports.th_type') }}</th>
                    <th>{{ __('reports.th_status') }}</th>
                    <th>{{ __('reports.th_amount') }}</th>
                    <th>{{ __('reports.th_gateway') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $payment)
                <tr>
                    <td class="num" dir="ltr">{{ $payment->created_at?->format('Y-m-d') ?? '—' }}</td>
                    <td class="font-medium text-ink">{{ $payment->auction ? \Illuminate\Support\Str::limit($payment->auction->localizedTitle(), 32) : '—' }}</td>
                    @if($showUser)<td>{{ $payment->user?->name ?? '—' }}</td>@endif
                    <td>{{ $payment->payment_type?->label() ?? '—' }}</td>
                    <td><span class="chip {{ $payment->status?->chipClass() }}"><span class="dot"></span>{{ $payment->status?->label() }}</span></td>
                    <td class="num"><x-money :centimes="$payment->amount" /></td>
                    <td class="lat text-start" dir="ltr">{{ $payment->gateway ?: '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $showUser ? 7 : 6 }}" class="text-center text-muted py-10">{{ __('reports.no_transactions') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-5 sm:px-6 py-4 border-t border-line">{{ $transactions->links() }}</div>
    @endif
</x-ui.card>
