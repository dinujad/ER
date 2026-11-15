@extends('accounting::layouts.app')

@section('title')
    @yield('tab-title')
@endsection

@section('css')
@include('accounting::layouts._acct_theme')
<style>
.acct-trans-shell{display:flex;flex-direction:column;gap:20px}
.acct-trans-grid{display:grid;grid-template-columns:1fr;gap:18px;align-items:start}
@media(min-width:992px){
    .acct-trans-grid{grid-template-columns:200px minmax(0,1fr)}
}
.acct-trans-side .list-group-item{border-radius:10px!important;margin-bottom:8px;border:1px solid #e9ecef;font-weight:600;font-size:.88rem;padding:12px 14px;color:#475569;text-decoration:none}
.acct-trans-side .list-group-item:hover{background:#f8fafc;color:#1e3a5f;border-color:#cbd5e1}
.acct-trans-side .list-group-item.active{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff!important;border-color:#1e3a5f;box-shadow:0 2px 10px rgba(30,58,95,.2)}
.acct-trans-main{min-width:0}
.tx-form-label{font-size:.78rem;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px;display:block}
#collapseFilter .form-group{margin-bottom:0}
#collapseFilter .acct-control,#collapseFilter .form-control.select2+.select2-container .select2-selection--single{min-height:42px}
.acct-card .nav-tabs .nav-link{color:#64748b;border:0;background:transparent;padding:.65rem 1rem}
.acct-card .nav-tabs .nav-link:hover{color:#1e3a5f}
.acct-card .nav-tabs .nav-link.active{color:#1e3a5f;background:#fff;border-bottom:3px solid #1e3a5f!important;border-radius:0;font-weight:600}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

@php
    $nav_items = [
        'accounting/transactions/sales?type=payment' => __('accounting::transactions.nav_sales'),
        'accounting/transactions/expenses' => __('accounting::transactions.nav_expenses'),
        'accounting/transactions/purchases?type=purchase_order' => __('accounting::transactions.nav_purchases'),
    ];
@endphp

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-arrow-left-right"></i></div>
        <div>
            <h4 class="mb-0">@yield('tab-title')</h4>
            <p class="mb-0 mt-1">@lang('accounting::transactions.hero_subtitle')</p>
        </div>
    </div>
</div>

<div class="acct-trans-shell">
    <div class="acct-trans-grid">
        <aside class="acct-trans-side">
            <div class="acct-card p-2">
                <div class="acct-card-head py-2 px-2 mb-2" style="border-bottom:1px solid #e9ecef">
                    <h6 class="mb-0 small text-uppercase text-muted fw-bold" style="font-size:.72rem;letter-spacing:.06em">@lang('accounting::lang.transactions')</h6>
                </div>
                <div class="d-flex flex-column gap-1 px-1 pb-2">
                    @foreach ($nav_items as $url => $label)
                        @php
                            if (str_contains($url, '/expenses')) {
                                $active = request()->is('accounting/transactions/expenses*');
                            } elseif (str_contains($url, '/purchases')) {
                                $active = request()->is('accounting/transactions/purchases*');
                            } else {
                                $active = request()->is('accounting/transactions/sales*');
                            }
                        @endphp
                        <a href="{{ url($url) }}" class="list-group-item list-group-item-action text-center {{ $active ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
        <div class="acct-trans-main">
            @yield('tab-content')
        </div>
    </div>
    @yield('modal-content')
</div>
@endsection

@section('javascript')
    @yield('tab-javascript')
    <script>
        if (document.getElementById('mapTransactionsModal')) {
            new Vue({
                el: '#mapTransactionsModal',
                data() {
                    return {
                        chart_of_accounts: {!! json_encode($chart_of_accounts ?? []) !!},
                        chart_of_account_id: '{{ old('chart_of_account_id') }}'
                    }
                },
            });
        }
    </script>
@endsection
