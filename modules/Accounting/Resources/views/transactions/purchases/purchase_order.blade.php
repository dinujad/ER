@extends('accounting::transactions.purchases.layout')
@section('sub-tab-title')
    {{ trans_choice('accounting::general.purchase', 1) }} {{ trans_choice('accounting::general.order', 1) }}
@endsection

@section('modal-content')
    @include('accounting::transactions.partials.map_transactions_modal', [
        'map_type' => 'debit',
        'mapping_for' => 'purchase_order',
    ])
@endsection

@section('sub-tab-content')
    <div class="p-3 border-bottom bg-white">
        @component('accounting::components.filters', ['title' => __('accounting::transactions.filters')])
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('po_list_filter_location_id', __('accounting::transactions.business_location') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('po_list_filter_location_id', $business_locations, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('po_list_filter_supplier_id', __('accounting::transactions.supplier') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('po_list_filter_supplier_id', $suppliers, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('po_list_filter_status', __('accounting::transactions.status') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('po_list_filter_status', $purchaseOrderStatuses, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                @if (!empty($shipping_statuses))
                    <div class="col-md-6 col-lg-3">
                        {!! acct_form_label('shipping_status', __('accounting::transactions.shipping_status') . ':', ['class' => 'tx-form-label']) !!}
                        {!! acct_form_select('shipping_status', $shipping_statuses, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                    </div>
                @endif
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('po_list_filter_date_range', __('accounting::transactions.date_range') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_text('po_list_filter_date_range', null, ['placeholder' => __('accounting::transactions.select_date_range'), 'class' => 'acct-control', 'readonly']) !!}
                </div>
            </div>
        @endcomponent
    </div>

    <div class="p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h6 class="mb-0"><i class="bi bi-journal-text me-2 text-muted"></i>{{ __('accounting::transactions.purchase_orders') }}</h6>
            @can('purchase_order.create')
                <a class="btn btn-sm btn-primary" href="{{ route('purchases.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('accounting::transactions.add') }}
                </a>
            @endcan
        </div>
        <div class="table-responsive rounded" style="border:1px solid #e9ecef;border-radius:12px">
            <table class="table table-hover align-middle mb-0" id="purchase_order_table" style="font-size:.875rem">
                <thead class="table-light">
                    <tr>
                        <th>@lang('accounting::general.mapping')</th>
                        <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                        <th>@lang('accounting::transactions.date')</th>
                        <th>@lang('accounting::transactions.ref_no')</th>
                        <th>@lang('accounting::transactions.location')</th>
                        <th>@lang('accounting::transactions.supplier')</th>
                        <th>@lang('accounting::transactions.status')</th>
                        <th>@lang('accounting::transactions.qty_remaining')</th>
                        <th>@lang('accounting::transactions.shipping_status')</th>
                        <th>@lang('accounting::transactions.added_by')</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade edit_pso_status_modal" tabindex="-1" role="dialog"></div>
@endsection

@section('sub-tab-javascript')
    <script src="{{ Module::asset('accounting:js/accounting_transactions.js') }}"></script>
    @include('accounting::transactions.js.purchase_order_transactions_js')
@endsection
