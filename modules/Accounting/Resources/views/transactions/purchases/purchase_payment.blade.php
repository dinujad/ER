@extends('accounting::transactions.purchases.layout')
@section('sub-tab-title')
    {{ trans_choice('accounting::general.purchase', 1) }} {{ trans_choice('accounting::general.payment', 1) }}
@endsection

@section('modal-content')
    @include('accounting::transactions.partials.map_transactions_modal', [
        'map_type' => 'debit',
        'mapping_for' => 'purchase_payment',
    ])
@endsection

@section('sub-tab-content')
    <div class="p-3 border-bottom bg-white">
        @component('accounting::components.filters', ['title' => __('accounting::transactions.filters')])
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('purchase_list_filter_location_id', __('accounting::transactions.business_location') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('purchase_list_filter_location_id', $business_locations, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('purchase_list_filter_supplier_id', __('accounting::transactions.supplier') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('purchase_list_filter_supplier_id', $suppliers, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('purchase_list_filter_status', __('accounting::transactions.purchase_status') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('purchase_list_filter_status', $orderStatuses, null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('purchase_list_filter_payment_status', __('accounting::transactions.payment_status') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_select('purchase_list_filter_payment_status', [
                        'paid' => __('accounting::transactions.status_paid'),
                        'due' => __('accounting::transactions.status_due'),
                        'partial' => __('accounting::transactions.status_partial'),
                        'overdue' => __('accounting::transactions.status_overdue'),
                    ], null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                </div>
                <div class="col-md-6 col-lg-3">
                    {!! acct_form_label('purchase_list_filter_date_range', __('accounting::transactions.date_range') . ':', ['class' => 'tx-form-label']) !!}
                    {!! acct_form_text('purchase_list_filter_date_range', null, ['placeholder' => __('accounting::transactions.select_date_range'), 'class' => 'acct-control', 'readonly']) !!}
                </div>
            </div>
        @endcomponent
    </div>

    <div class="p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h6 class="mb-0"><i class="bi bi-bag-check me-2 text-muted"></i>{{ __('accounting::transactions.all_purchases') }}</h6>
            @can('purchase.create')
                <a class="btn btn-sm btn-primary" href="{{ route('purchases.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('accounting::transactions.add') }}
                </a>
            @endcan
        </div>
        <div class="table-responsive rounded" style="border:1px solid #e9ecef;border-radius:12px">
            <table class="table table-hover align-middle mb-0" id="purchase_table" style="font-size:.875rem">
                <thead class="table-light">
                    <tr>
                        <th>@lang('accounting::general.mapping')</th>
                        <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                        <th>@lang('accounting::transactions.date')</th>
                        <th>@lang('accounting::transactions.ref_no')</th>
                        <th>@lang('accounting::transactions.location')</th>
                        <th>@lang('accounting::transactions.supplier')</th>
                        <th>@lang('accounting::transactions.purchase_status')</th>
                        <th>@lang('accounting::transactions.payment_status')</th>
                        <th>@lang('accounting::transactions.grand_total')</th>
                        <th>@lang('accounting::transactions.purchase_due')</th>
                        <th>@lang('accounting::transactions.added_by')</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot class="table-light">
                    <tr class="fw-semibold text-center">
                        <td colspan="6" class="text-start ps-3">@lang('accounting::transactions.total'):</td>
                        <td class="footer_status_count"></td>
                        <td class="footer_payment_status_count"></td>
                        <td class="footer_purchase_total"></td>
                        <td class="text-start small">
                            @lang('accounting::transactions.purchase_due') — <span class="footer_total_due"></span><br>
                            @lang('accounting::transactions.purchase_return') — <span class="footer_total_purchase_return_due"></span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <section id="receipt_section" class="print_section"></section>
@endsection

@section('sub-tab-javascript')
    <script src="{{ Module::asset('accounting:js/accounting_transactions.js') }}"></script>
    <script src="{{ Module::asset('accounting:js/purchase_payment.js?v=' . ($asset_v ?? '1')) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . ($asset_v ?? '1')) }}"></script>
@endsection
