@php
    $u = auth()->user();
    $canView = $u && ($u->isSystemAdmin() || $u->can_access('accounting.view'));
    $canManage = $u && ($u->isSystemAdmin() || $u->can_access('accounting.manage'));
@endphp

<aside class="acct-ws-sidebar" id="acctWorkspaceSidebar">
    <div class="acct-ws-brand">
        <div class="acct-ws-brand-mark"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
            <div class="acct-ws-brand-title">Accounting</div>
            <div class="acct-ws-brand-sub">Workspace · LKR</div>
        </div>
    </div>

    <nav class="acct-ws-nav">
        @if($canView)
        <div class="acct-ws-section">Dashboard</div>
        <a href="{{ url('accounting/dashboard') }}" class="acct-ws-link {{ request()->is('accounting/dashboard*') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Overview
        </a>

        <div class="acct-ws-section">Chart of Accounts</div>
        <a href="{{ url('accounting/chart_of_account') }}" class="acct-ws-link {{ request()->is('accounting/chart_of_account*') ? 'active' : '' }}">
            <i class="bi bi-diagram-3"></i> All Accounts
        </a>
        <a href="{{ url('accounting/account-register') }}" class="acct-ws-link {{ request()->is('accounting/account-register*') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i> Account Register
        </a>

        <div class="acct-ws-section">Journal Entries</div>
        <a href="{{ url('accounting/journal_entry') }}" class="acct-ws-link {{ request()->is('accounting/journal_entry*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> All Journals
        </a>
        <a href="{{ url('accounting/manual-journals') }}" class="acct-ws-link {{ request()->is('accounting/manual-journals*') && !request()->is('accounting/manual-journals/approvals') ? 'active' : '' }}">
            <i class="bi bi-journal-plus"></i> Multi-line Journals
        </a>
        <a href="{{ url('accounting/manual-journals/create') }}" class="acct-ws-link">
            <i class="bi bi-plus-square"></i> New Journal
        </a>
        <a href="{{ url('accounting/manual-journals?status=draft') }}" class="acct-ws-link">
            <i class="bi bi-pencil-square"></i> Draft Journals
        </a>
        <a href="{{ url('accounting/manual-journals/approvals') }}" class="acct-ws-link {{ request()->is('accounting/manual-journals/approvals') ? 'active' : '' }}">
            <i class="bi bi-check2-square"></i> Approval Queue
        </a>
        <a href="{{ url('accounting/recurring-journals') }}" class="acct-ws-link {{ request()->is('accounting/recurring-journals*') ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat"></i> Recurring Journals
        </a>
        <a href="{{ url('accounting/transactions/sales') }}" class="acct-ws-link {{ request()->is('accounting/transactions*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i> ERP Transactions
        </a>
        <a href="{{ url('accounting/transfers') }}" class="acct-ws-link {{ request()->is('accounting/transfers*') ? 'active' : '' }}">
            <i class="bi bi-shuffle"></i> Transfers
        </a>

        <div class="acct-ws-section">Banking</div>
        <a href="{{ url('accounting/reconcile') }}" class="acct-ws-link {{ request()->is('accounting/reconcile*') ? 'active' : '' }}">
            <i class="bi bi-bank"></i> Bank Reconciliation
        </a>
        <a href="{{ url('accounting/bank-statements') }}" class="acct-ws-link {{ request()->is('accounting/bank-statements*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> Statement Imports
        </a>

        <div class="acct-ws-section">Receivables</div>
        <a href="{{ url('accounting/receivables/customer-ledger') }}" class="acct-ws-link {{ request()->is('accounting/receivables*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Customer Ledger
        </a>
        <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="acct-ws-link {{ request()->is('report/accounting/accounts_receivable*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> AR Ageing
        </a>

        <div class="acct-ws-section">Payables</div>
        <a href="{{ url('accounting/payables/supplier-ledger') }}" class="acct-ws-link {{ request()->is('accounting/payables*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Supplier Ledger
        </a>
        <a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}" class="acct-ws-link {{ request()->is('report/accounting/accounts_payable*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> AP Ageing
        </a>

        <div class="acct-ws-section">Accounting Controls</div>
        <a href="{{ url('accounting/periods') }}" class="acct-ws-link {{ request()->is('accounting/periods*') ? 'active' : '' }}">
            <i class="bi bi-lock"></i> Accounting Periods
        </a>
        <a href="{{ url('accounting/audit-log') }}" class="acct-ws-link {{ request()->is('accounting/audit-log*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> Audit Log
        </a>

        <div class="acct-ws-section">Financial Reports</div>
        <a href="{{ url('report/accounting/profit_and_loss') }}" class="acct-ws-link {{ request()->is('report/accounting/profit_and_loss*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Profit &amp; Loss
        </a>
        <a href="{{ url('report/accounting/balance_sheet') }}" class="acct-ws-link {{ request()->is('report/accounting/balance_sheet*') ? 'active' : '' }}">
            <i class="bi bi-pie-chart"></i> Balance Sheet
        </a>
        <a href="{{ url('report/accounting/trial_balance') }}" class="acct-ws-link {{ request()->is('report/accounting/trial_balance*') ? 'active' : '' }}">
            <i class="bi bi-list-check"></i> Trial Balance
        </a>
        <a href="{{ url('report/accounting/ledger') }}" class="acct-ws-link {{ request()->is('report/accounting/ledger*') ? 'active' : '' }}">
            <i class="bi bi-book"></i> General Ledger
        </a>
        <a href="{{ url('report/accounting/cash_flow') }}" class="acct-ws-link {{ request()->is('report/accounting/cash_flow*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Cash Flow
        </a>
        <a href="{{ url('report/accounting/journal') }}" class="acct-ws-link {{ request()->is('report/accounting/journal') && !request()->is('report/accounting/journal_*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i> Journal Report
        </a>
        <a href="{{ url('report/accounting') }}" class="acct-ws-link {{ request()->is('report/accounting') && !request()->is('report/accounting/*') ? 'active' : '' }}">
            <i class="bi bi-grid"></i> All Reports
        </a>
        <a href="{{ url('accounting/budget') }}" class="acct-ws-link {{ request()->is('accounting/budget*') || request()->is('report/accounting/budget*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> Budget
        </a>

        @if($canManage)
        <div class="acct-ws-section">Settings</div>
        <a href="{{ url('accounting/settings/account_subtypes') }}" class="acct-ws-link {{ request()->is('accounting/settings*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Account Settings
        </a>
        @endif
        @endif
    </nav>

    <div class="acct-ws-sidebar-foot">
        <a href="{{ url('/erp/dashboard') }}" class="acct-ws-back"><i class="bi bi-arrow-left-circle"></i> Back to ERP</a>
    </div>
</aside>
<div class="acct-ws-backdrop" id="acctWorkspaceBackdrop"></div>
