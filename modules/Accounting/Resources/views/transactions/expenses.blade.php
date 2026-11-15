@extends('accounting::layouts.transactions_layout')
@section('tab-title')
    {{ trans_choice('accounting::general.expense', 2) }}
@endsection

@section('modal-content')
    @include('accounting::transactions.partials.map_transactions_modal', [
        'map_type' => 'debit',
        'mapping_for' => 'expense',
    ])
@endsection

@section('tab-content')
    <div class="acct-card overflow-hidden">
        <div class="p-3 border-bottom bg-white">
            @component('accounting::components.filters', ['title' => __('accounting::transactions.filters')])
                <div class="row g-3">
                    @if (auth()->user()->can('all_expense.access'))
                        <div class="col-md-6 col-lg-4">
                            {!! acct_form_label('location_id', __('accounting::transactions.business_location') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('location_id', $business_locations, null, ['class' => 'acct-control select2', 'style' => 'width:100%']) !!}
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {!! acct_form_label('expense_for', __('accounting::transactions.expense_for') . ':', ['class' => 'tx-form-label']) !!}
                            {!! acct_form_select('expense_for', $users, null, ['class' => 'acct-control select2', 'style' => 'width:100%']) !!}
                        </div>
                    @endif
                    <div class="col-md-6 col-lg-4">
                        {!! acct_form_label('expense_category_id', __('accounting::transactions.expense_category') . ':', ['class' => 'tx-form-label']) !!}
                        {!! acct_form_select('expense_category_id', $categories, null, ['placeholder' => __('accounting::transactions.all_categories'), 'class' => 'acct-control select2', 'style' => 'width:100%', 'id' => 'expense_category_id']) !!}
                    </div>
                    <div class="col-md-6 col-lg-4">
                        {!! acct_form_label('expense_date_range', __('accounting::transactions.date_range') . ':', ['class' => 'tx-form-label']) !!}
                        {!! acct_form_text('date_range', null, ['placeholder' => __('accounting::transactions.select_date_range'), 'class' => 'acct-control', 'id' => 'expense_date_range', 'readonly']) !!}
                    </div>
                    <div class="col-md-6 col-lg-4">
                        {!! acct_form_label('expense_payment_status', __('accounting::transactions.payment_status') . ':', ['class' => 'tx-form-label']) !!}
                        {!! acct_form_select('expense_payment_status', [
                            'paid' => __('accounting::transactions.status_paid'),
                            'due' => __('accounting::transactions.status_due'),
                            'partial' => __('accounting::transactions.status_partial'),
                        ], null, ['class' => 'acct-control select2', 'style' => 'width:100%', 'placeholder' => __('accounting::transactions.all')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        <div class="p-3">
            <h6 class="mb-3"><i class="bi bi-wallet2 me-2 text-muted"></i>@lang('accounting::transactions.records_expense')</h6>
            <div class="table-responsive rounded" style="border:1px solid #e9ecef;border-radius:12px">
                <table class="table table-hover align-middle mb-0" id="expense_transactions_table" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr>
                            <th>@lang('accounting::general.mapping')</th>
                            <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                            <th>@lang('accounting::transactions.date')</th>
                            <th>@lang('accounting::transactions.ref_no')</th>
                            <th>@lang('accounting::transactions.recur_details')</th>
                            <th>@lang('accounting::transactions.expense_category')</th>
                            <th>@lang('accounting::transactions.location')</th>
                            <th>@lang('accounting::transactions.payment_status')</th>
                            <th class="d-none">@lang('accounting::transactions.tax')</th>
                            <th>@lang('accounting::transactions.total_amount')</th>
                            <th>@lang('accounting::transactions.payment_due')</th>
                            <th>@lang('accounting::transactions.expense_for')</th>
                            <th>@lang('accounting::transactions.contact')</th>
                            <th>@lang('accounting::transactions.expense_note')</th>
                            <th>@lang('accounting::transactions.added_by')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="table-light">
                        <tr class="fw-semibold text-center">
                            <td colspan="7" class="text-start ps-3">@lang('accounting::transactions.total'):</td>
                            <td class="footer_payment_status_count"></td>
                            <td></td>
                            <td class="footer_expense_total"></td>
                            <td class="footer_total_due"></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('tab-javascript')
    <script src="{{ Module::asset('accounting:js/accounting_transactions.js') }}"></script>
    <script src="{{ Module::asset('accounting:js/expense_transactions.js') }}"></script>
    <script src="{{ asset('js/payment.js?v=' . ($asset_v ?? '1')) }}"></script>
@endsection
