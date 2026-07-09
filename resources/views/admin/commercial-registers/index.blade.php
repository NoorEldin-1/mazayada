@extends('layouts.admin')

@section('title', __('admin.commercial_registers.manage_title'))
@section('page-title', __('admin.commercial_registers.manage_title'))

@section('content')

@if(session('success'))
<div style="background:#E5F3EC;color:#1d6045;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:13px">
    {{ session('success') }}
</div>
@endif

<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('admin.commercial_registers.th_company') }}</th>
            <th>{{ __('admin.commercial_registers.th_register_number') }}</th>
            <th>{{ __('admin.th_name') }}</th>
            <th>{{ __('admin.commercial_registers.th_submitted_date') }}</th>
            <th>{{ __('common.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($registers as $register)
            <tr>
                <td>{{ $register->company_name }}</td>
                <td class="num" style="direction:ltr;text-align:right">{{ $register->register_number }}</td>
                <td>{{ $register->user?->fullNameAr() }}</td>
                <td>{{ $register->submitted_at?->format('Y-m-d H:i') }}</td>
                <td>
                    <x-ui.btn variant="primary" size="sm" :href="route('admin.commercial-registers.show', $register)">
                        {{ __('admin.commercial_registers.review') }}
                    </x-ui.btn>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-8">{{ __('admin.commercial_registers.no_pending') }}</td>
            </tr>
        @endforelse
    </tbody>
</x-ui.table>

<div class="mt-6">
    {{ $registers->links() }}
</div>

@endsection
