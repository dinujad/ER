@extends('accounting::layouts.transactions_layout')
@section('tab-title')
    {{ trans_choice('accounting::general.sales', 1) }} — @yield('sub-tab-title')
@endsection

@section('tab-content')
    @php
        $saleType = request('type', 'payment');
    @endphp

    <div class="acct-card mb-3 overflow-hidden">
        <div class="border-bottom bg-light px-3 pt-2">
            <ul class="nav nav-tabs border-0 gap-1 flex-nowrap" role="tablist" style="flex-wrap:nowrap;overflow-x:auto">
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-top {{ $saleType === 'payment' ? 'active fw-semibold' : 'text-secondary' }}"
                        href="{{ url('accounting/transactions/sales?type=payment') }}">
                        <i class="bi bi-credit-card me-1"></i>{{ trans_choice('accounting::general.payment', 2) }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-top {{ $saleType === 'invoice' ? 'active fw-semibold' : 'text-secondary' }}"
                        href="{{ url('accounting/transactions/sales?type=invoice') }}">
                        <i class="bi bi-file-text me-1"></i>{{ trans_choice('accounting::general.invoice', 2) }}
                    </a>
                </li>
            </ul>
        </div>

        <div class="p-3 border-bottom bg-white">
            @component('accounting::components.filters', ['title' => __('accounting::transactions.filters')])
                <div class="row g-3">
                    @if (empty($only) || in_array('sell_list_filter_location_id', $only))
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('sell_list_filter_location_id', __('accounting::transactions.business_location') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('sell_list_filter_location_id', $business_locations, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                        </div>
                    @endif
                    @if ((empty($only) || in_array('sell_list_filter_customer_id', $only)) && $saleType === 'invoice')
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('sell_list_filter_customer_id', __('accounting::transactions.customer') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('sell_list_filter_customer_id', $customers, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                        </div>
                    @endif
                    @if (empty($only) || in_array('sell_list_filter_payment_status', $only))
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('sell_list_filter_payment_status', __('accounting::transactions.payment_status') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('sell_list_filter_payment_status', [
                                'paid' => __('accounting::transactions.status_paid'),
                                'due' => __('accounting::transactions.status_due'),
                                'partial' => __('accounting::transactions.status_partial'),
                                'overdue' => __('accounting::transactions.status_overdue'),
                            ], null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                        </div>
                    @endif
                    @if (empty($only) || in_array('sell_list_filter_date_range', $only))
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('sell_list_filter_date_range', __('accounting::transactions.date_range') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_text('sell_list_filter_date_range', null, ['placeholder' => __('accounting::transactions.select_date_range'), 'class' => 'acct-control', 'readonly']) !!}
                        </div>
                    @endif
                    @if ((empty($only) || in_array('created_by', $only)) && !empty($sales_representative))
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('created_by', __('accounting::transactions.user') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('created_by', $sales_representative, null, ['class' => 'acct-control select2', 'style' => 'width:100%']) !!}
                        </div>
                    @endif
                    @if (empty($only) || in_array('sales_cmsn_agnt', $only))
                        @if (!empty($is_cmsn_agent_enabled))
                            <div class="col-md-6 col-lg-3">
                                {!! acct_form_label('sales_cmsn_agnt', __('accounting::transactions.commission_agent') . ':', ['class' => 'tx-form-label']) !!}
                                {!! acct_form_select('sales_cmsn_agnt', $commission_agents, null, ['class' => 'acct-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        @endif
                    @endif
                    @if (empty($only) || in_array('service_staffs', $only))
                        @if (!empty($service_staffs))
                            <div class="col-md-6 col-lg-3">
                                {!! acct_form_label('service_staffs', __('accounting::transactions.service_staff') . ':', ['class' => 'tx-form-label']) !!}
                                {!! acct_form_select('service_staffs', $service_staffs, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                            </div>
                        @endif
                    @endif
                    @if (!empty($sources))
                        <div class="col-md-6 col-lg-3">
                            {!! acct_form_label('sell_list_filter_source', __('accounting::transactions.sources') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('sell_list_filter_source', $sources, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                        </div>
                    @endif
                </div>
            @endcomponent
        </div>

        <div class="p-0">
            @yield('sub-tab-content')
        </div>
    </div>
@endsection

@section('tab-javascript')
    @yield('sub-tab-javascript')
@endsection
