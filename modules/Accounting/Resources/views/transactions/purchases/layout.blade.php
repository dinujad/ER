@extends('accounting::layouts.transactions_layout')
@section('tab-title')
    @yield('sub-tab-title')
@endsection

@section('tab-content')
    @php
        $purchaseType = request('type', 'purchase_order');
    @endphp

    <div class="acct-card mb-3 overflow-hidden">
        <div class="border-bottom bg-light px-3 pt-2">
            <ul class="nav nav-tabs border-0 gap-1 flex-nowrap" role="tablist" style="flex-wrap:nowrap;overflow-x:auto">
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-top {{ $purchaseType === 'purchase_order' ? 'active fw-semibold' : 'text-secondary' }}"
                        href="{{ url('accounting/transactions/purchases?type=purchase_order') }}">
                        <i class="bi bi-cart-check me-1"></i>{{ trans_choice('accounting::general.order', 1) }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-top {{ $purchaseType === 'purchase_payment' ? 'active fw-semibold' : 'text-secondary' }}"
                        href="{{ url('accounting/transactions/purchases?type=purchase_payment') }}">
                        <i class="bi bi-cash-coin me-1"></i>{{ trans_choice('accounting::general.payment', 1) }}
                    </a>
                </li>
            </ul>
        </div>
        <div class="p-0">
            @yield('sub-tab-content')
        </div>
    </div>
@endsection

@section('tab-javascript')
    @yield('sub-tab-javascript')
@endsection
