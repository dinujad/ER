{{-- Horizontal accounting menu (Bootstrap 5, matches ERP) --}}
<div class="accounting-top-nav no-print mb-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center gap-1">
                <a href="{{ url('accounting/dashboard') }}" class="btn btn-sm {{ request()->is('accounting/dashboard*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-journal-bookmark-fill me-1"></i>{{ __('accounting::lang.accounting') }}
                </a>
                <a href="{{ url('accounting/chart_of_account') }}" class="btn btn-sm {{ request()->is('accounting/chart_of_account*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.view_charts_of_accounts')
                </a>
                <a href="{{ url('accounting/journal_entry') }}" class="btn btn-sm {{ request()->is('accounting/journal_entry*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.journal_of_entries')
                </a>
                <a href="{{ url('accounting/transfers') }}" class="btn btn-sm {{ request()->is('accounting/transfers*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ trans_choice('accounting::lang.transfer', 2) }}
                </a>
                <a href="{{ url('accounting/transactions/sales') }}" class="btn btn-sm {{ request()->is('accounting/transactions*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.transactions')
                </a>
                <a href="{{ url('accounting/reconcile') }}" class="btn btn-sm {{ request()->is('accounting/reconcile*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.reconcile')
                </a>
                <a href="{{ url('accounting/budget?view=monthly&year=' . get_financial_year()) }}" class="btn btn-sm {{ request()->is('accounting/budget*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ trans('accounting::general.budgeting') }}
                </a>
                <a href="{{ url('report/accounting') }}" class="btn btn-sm {{ request()->is('report/accounting*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.reports')
                </a>
                <a href="{{ url('accounting/settings/account_subtypes') }}" class="btn btn-sm {{ request()->is('accounting/settings*') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    @lang('accounting::lang.settings')
                </a>
            </div>
        </div>
    </div>
</div>
