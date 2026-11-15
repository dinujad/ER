@extends('accounting::transactions.sales.layout')
@section('sub-tab-title')
    {{ trans_choice('accounting::general.payment', 2) }}
@endsection

@section('modal-content')
    @include('accounting::transactions.partials.map_transactions_modal', [
        'map_type' => 'credit',
        'mapping_for' => 'payment',
    ])
@endsection

@section('sub-tab-content')
    <div class="p-3">
        @if (auth()->user()->can('direct_sell.view') ||
            auth()->user()->can('view_own_sell_only') ||
            auth()->user()->can('view_commission_agent_sell'))
            @php
                $custom_labels = json_decode(session('business.custom_labels', '{}'), true) ?: [];
            @endphp
            <div class="acct-card-head border-0 px-0 pt-0 pb-2">
                <h6 class="mb-0"><i class="bi bi-table me-2 text-muted"></i>@lang('accounting::transactions.records_sale')</h6>
            </div>
            <div class="table-responsive rounded-bottom" style="border:1px solid #e9ecef;border-radius:0 0 12px 12px">
                <table class="table table-hover align-middle mb-0" id="sell_table" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"></th>
                            <th>@lang('accounting::general.mapping')</th>
                            <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                            <th>@lang('accounting::transactions.date')</th>
                            <th>@lang('accounting::transactions.invoice_no')</th>
                            <th class="d-none">@lang('accounting::transactions.customer_name')</th>
                            <th>@lang('accounting::transactions.location')</th>
                            <th>@lang('accounting::transactions.payment_status')</th>
                            <th>@lang('accounting::transactions.payment_method')</th>
                            <th>@lang('accounting::transactions.total_amount')</th>
                            <th>@lang('accounting::transactions.total_paid')</th>
                            <th>@lang('accounting::transactions.amount_due')</th>
                            <th>@lang('accounting::transactions.return_due')</th>
                            <th>@lang('accounting::transactions.total_items')</th>
                            <th>@lang('accounting::transactions.types_of_service')</th>
                            <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('accounting::transactions.service_custom_field') }}</th>
                            <th>@lang('accounting::transactions.added_by')</th>
                            <th>@lang('accounting::transactions.sell_note')</th>
                            <th>@lang('accounting::transactions.staff_note')</th>
                            <th>@lang('accounting::transactions.table_number')</th>
                            <th>@lang('accounting::transactions.service_staff')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="table-light">
                        <tr class="text-center fw-semibold">
                            <td colspan="7" class="text-start ps-3">@lang('accounting::transactions.total'):</td>
                            <td class="footer_payment_status_count"></td>
                            <td class="payment_method_count"></td>
                            <td class="footer_sale_total"></td>
                            <td class="footer_total_paid"></td>
                            <td class="footer_total_remaining"></td>
                            <td class="footer_total_sell_return_due"></td>
                            <td colspan="2"></td>
                            <td class="service_type_count"></td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('sub-tab-javascript')
    <script src="{{ Module::asset('accounting:js/accounting_transactions.js') }}"></script>
    @include('accounting::transactions.js.payment_js')
    <script src="{{ asset('js/payment.js?v=' . ($asset_v ?? '1')) }}"></script>
@endsection
