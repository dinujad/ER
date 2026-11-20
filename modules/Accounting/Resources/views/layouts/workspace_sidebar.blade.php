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
        <a href="{{ url('accounting/recurring-journals?document_type=journal') }}" class="acct-ws-link {{ request()->is('accounting/recurring-journals*') && request('document_type','journal')==='journal' ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat"></i> Recurring Journals
        </a>
        <a href="{{ url('accounting/recurring-journals?document_type=invoice') }}" class="acct-ws-link {{ request('document_type')==='invoice' ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Recurring Invoices
        </a>
        <a href="{{ url('accounting/recurring-journals?document_type=bill') }}" class="acct-ws-link {{ request('document_type')==='bill' ? 'active' : '' }}">
            <i class="bi bi-file-earmark-ruled"></i> Recurring Bills
        </a>
        <a href="{{ url('accounting/recurring-journals/history') }}" class="acct-ws-link {{ request()->is('accounting/recurring-journals/history') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Execution History
        </a>
        <a href="{{ url('accounting/transactions/sales') }}" class="acct-ws-link {{ request()->is('accounting/transactions*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i> ERP Transactions
        </a>
        <a href="{{ url('accounting/transfers') }}" class="acct-ws-link {{ request()->is('accounting/transfers*') ? 'active' : '' }}">
            <i class="bi bi-shuffle"></i> Transfers
        </a>

        <div class="acct-ws-section">Banking</div>
        <a href="{{ url('accounting/banking') }}" class="acct-ws-link {{ request()->is('accounting/banking*') ? 'active' : '' }}">
            <i class="bi bi-bank2"></i> Bank Accounts
        </a>
        <a href="{{ url('accounting/phase7/reconcile') }}" class="acct-ws-link {{ request()->is('accounting/phase7/reconcile*') ? 'active' : '' }}">
            <i class="bi bi-check2-square"></i> Review Transactions
        </a>
        <a href="{{ url('accounting/reconcile') }}" class="acct-ws-link {{ request()->is('accounting/reconcile*') ? 'active' : '' }}">
            <i class="bi bi-bank"></i> Reconciliation
        </a>
        <a href="{{ url('accounting/bank-statements') }}" class="acct-ws-link {{ request()->is('accounting/bank-statements*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> Statement Imports
        </a>
        <a href="{{ url('accounting/bank-matching') }}" class="acct-ws-link {{ request()->is('accounting/bank-matching*') ? 'active' : '' }}">
            <i class="bi bi-link-45deg"></i> Transaction Matching
        </a>
        <a href="{{ url('accounting/bank-reconciliation-report') }}" class="acct-ws-link {{ request()->is('accounting/bank-reconciliation-report*') ? 'active' : '' }}">
            <i class="bi bi-filetype-pdf"></i> Recon Report PDF
        </a>

        <div class="acct-ws-section">Sales &amp; Invoices</div>
        <a href="{{ url('accounting/phase6/invoices') }}" class="acct-ws-link {{ request()->is('accounting/phase6/invoices*') ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i> Sales Invoices
        </a>
        <a href="{{ url('accounting/phase6/receivables/overdue') }}" class="acct-ws-link {{ request()->is('accounting/phase6/receivables*') ? 'active' : '' }}">
            <i class="bi bi-alarm"></i> Overdue &amp; Reminders
        </a>
        <a href="{{ url('accounting/phase7/reminders') }}" class="acct-ws-link {{ request()->is('accounting/phase7/reminders*') ? 'active' : '' }}">
            <i class="bi bi-chat-dots"></i> Reminder History
        </a>
        <a href="{{ url('accounting/phase7/recurring-queue') }}" class="acct-ws-link {{ request()->is('accounting/phase7/recurring-queue*') ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat"></i> Recurring Queue
        </a>
        <a href="{{ url('accounting/phase6/credit-notes') }}" class="acct-ws-link {{ request()->is('accounting/phase6/credit-notes*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-minus"></i> Credit Notes
        </a>
        <a href="{{ url('accounting/phase6/account-transactions') }}" class="acct-ws-link {{ request()->is('accounting/phase6/account-transactions*') ? 'active' : '' }}">
            <i class="bi bi-search"></i> Account Transactions
        </a>

        <div class="acct-ws-section">VAT &amp; SSCL</div>
        <a href="{{ url('accounting/phase6/vat-return') }}" class="acct-ws-link {{ request()->is('accounting/phase6/vat-return*') ? 'active' : '' }}">
            <i class="bi bi-percent"></i> VAT &amp; SSCL Return
        </a>
        <a href="{{ url('accounting/phase6/tax-invoice-register') }}" class="acct-ws-link {{ request()->is('accounting/phase6/tax-invoice-register*') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i> Tax Invoice Register
        </a>

        <div class="acct-ws-section">Receivables</div>
        <a href="{{ url('accounting/receivables/customer-ledger') }}" class="acct-ws-link {{ request()->is('accounting/receivables/customer-ledger*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Customer Ledger
        </a>
        <a href="{{ url('accounting/receivables/payment-allocation') }}" class="acct-ws-link {{ request()->is('accounting/receivables/payment-allocation*') ? 'active' : '' }}">
            <i class="bi bi-cash-coin"></i> Payment Allocation
        </a>
        <a href="{{ url('accounting/receivables/customer-statement') }}" class="acct-ws-link {{ request()->is('accounting/receivables/customer-statement*') ? 'active' : '' }}">
            <i class="bi bi-filetype-pdf"></i> Customer Statements
        </a>
        <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="acct-ws-link {{ request()->is('report/accounting/accounts_receivable*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> AR Ageing
        </a>

        <div class="acct-ws-section">Payables</div>
        <a href="{{ url('accounting/phase6/bills') }}" class="acct-ws-link {{ request()->is('accounting/phase6/bills') || request()->is('accounting/phase6/bills/[0-9]*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i> Bills
        </a>
        <a href="{{ url('accounting/phase6/bills/payment-run') }}" class="acct-ws-link {{ request()->is('accounting/phase6/bills/payment-run*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Pay Bills
        </a>
        <a href="{{ url('accounting/phase7/payment-runs') }}" class="acct-ws-link {{ request()->is('accounting/phase7/payment-runs*') ? 'active' : '' }}">
            <i class="bi bi-collection"></i> Payment Runs
        </a>
        <a href="{{ url('accounting/payables/supplier-ledger') }}" class="acct-ws-link {{ request()->is('accounting/payables/supplier-ledger*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Supplier Ledger
        </a>
        <a href="{{ url('accounting/payables/payment-allocation') }}" class="acct-ws-link {{ request()->is('accounting/payables/payment-allocation*') ? 'active' : '' }}">
            <i class="bi bi-cash-coin"></i> Payment Allocation
        </a>
        <a href="{{ url('accounting/payables/supplier-statement') }}" class="acct-ws-link {{ request()->is('accounting/payables/supplier-statement*') ? 'active' : '' }}">
            <i class="bi bi-filetype-pdf"></i> Supplier Statements
        </a>
        <a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}" class="acct-ws-link {{ request()->is('report/accounting/accounts_payable*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> AP Ageing
        </a>

        <div class="acct-ws-section">Fixed Assets</div>
        <a href="{{ url('accounting/fixed-assets') }}" class="acct-ws-link {{ request()->is('accounting/fixed-assets*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Asset Register
        </a>
        <a href="{{ url('accounting/fixed-asset-categories') }}" class="acct-ws-link {{ request()->is('accounting/fixed-asset-categories*') ? 'active' : '' }}">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="{{ url('accounting/depreciation/runs') }}" class="acct-ws-link {{ request()->is('accounting/depreciation*') ? 'active' : '' }}">
            <i class="bi bi-graph-down"></i> Depreciation Runs
        </a>
        <a href="{{ url('accounting/asset-disposals') }}" class="acct-ws-link {{ request()->is('accounting/asset-disposals*') ? 'active' : '' }}">
            <i class="bi bi-trash3"></i> Disposal
        </a>
        <a href="{{ url('accounting/purchase-capitalization') }}" class="acct-ws-link {{ request()->is('accounting/purchase-capitalization*') ? 'active' : '' }}">
            <i class="bi bi-bag-check"></i> Purchase-linked Assets
        </a>
        <a href="{{ url('accounting/fixed-assets/reports/register/pdf') }}" class="acct-ws-link {{ request()->is('accounting/fixed-assets/reports*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> Asset Reports
        </a>

        <div class="acct-ws-section">Loans</div>
        <a href="{{ url('accounting/loans') }}" class="acct-ws-link {{ request()->is('accounting/loans*') ? 'active' : '' }}">
            <i class="bi bi-bank2"></i> Loan Register
        </a>

        <div class="acct-ws-section">Cost Accounting</div>
        <a href="{{ url('accounting/cost-centers') }}" class="acct-ws-link {{ request()->is('accounting/cost-centers*') ? 'active' : '' }}">
            <i class="bi bi-diagram-2"></i> Cost Centers
        </a>
        <a href="{{ url('accounting/cost-allocations') }}" class="acct-ws-link {{ request()->is('accounting/cost-allocations*') ? 'active' : '' }}">
            <i class="bi bi-pie-chart"></i> Allocations
        </a>
        <a href="{{ url('accounting/cost-center-reports/profit-and-loss') }}" class="acct-ws-link {{ request()->is('accounting/cost-center-reports*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i> CC P&amp;L
        </a>
        <a href="{{ url('accounting/job-costs') }}" class="acct-ws-link {{ request()->is('accounting/job-costs*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-data"></i> Job Costing
        </a>

        <div class="acct-ws-section">Manufacturing Accounting</div>
        <a href="{{ url('accounting/manufacturing-accounting') }}" class="acct-ws-link {{ request()->is('accounting/manufacturing-accounting*') ? 'active' : '' }}">
            <i class="bi bi-gear-wide-connected"></i> Production Costing
        </a>
        <a href="{{ url('accounting/manufacturing-overhead/rules') }}" class="acct-ws-link {{ request()->is('accounting/manufacturing-overhead*') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i> Overhead Rules
        </a>

        <div class="acct-ws-section">Budgeting</div>
        <a href="{{ url('accounting/budget-versions') }}" class="acct-ws-link {{ request()->is('accounting/budget-versions*') && !request()->is('accounting/budget-versions/vs-actual') ? 'active' : '' }}">
            <i class="bi bi-collection"></i> Budget Versions
        </a>
        <a href="{{ url('accounting/budget-versions/vs-actual') }}" class="acct-ws-link {{ request()->is('accounting/budget-versions/vs-actual*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-steps"></i> Budget vs Actual
        </a>

        <div class="acct-ws-section">Accounting Controls</div>
        <a href="{{ url('accounting/phase7/close') }}" class="acct-ws-link {{ request()->is('accounting/phase7/close*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i> Month-end Close
        </a>
        <a href="{{ url('accounting/periods') }}" class="acct-ws-link {{ request()->is('accounting/periods*') ? 'active' : '' }}">
            <i class="bi bi-lock"></i> Accounting Periods
        </a>
        <a href="{{ url('accounting/audit-log') }}" class="acct-ws-link {{ request()->is('accounting/audit-log*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> Audit Log
        </a>
        <a href="{{ url('accounting/phase6/audit-trail') }}" class="acct-ws-link {{ request()->is('accounting/phase6/audit-trail*') ? 'active' : '' }}">
            <i class="bi bi-person-lines-fill"></i> Audit Trail (who did what)
        </a>
        <a href="{{ url('accounting/phase6/recode') }}" class="acct-ws-link {{ request()->is('accounting/phase6/recode*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i> Find &amp; Recode
        </a>

        <div class="acct-ws-section">AI &amp; Automation</div>
        <a href="{{ url('accounting/phase5/assistant') }}" class="acct-ws-link {{ request()->is('accounting/phase5/assistant*') ? 'active' : '' }}">
            <i class="bi bi-robot"></i> AI Accounting Assistant
        </a>
        <a href="{{ url('accounting/phase5/ocr') }}" class="acct-ws-link {{ request()->is('accounting/phase5/ocr*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-image"></i> Smart Document Capture
        </a>
        <a href="{{ url('accounting/phase5/categorization') }}" class="acct-ws-link {{ request()->is('accounting/phase5/categorization*') ? 'active' : '' }}">
            <i class="bi bi-tags"></i> Categorization Review
        </a>
        <a href="{{ url('accounting/phase5/alerts') }}" class="acct-ws-link {{ request()->is('accounting/phase5/alerts*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i> Financial Alerts
        </a>
        <a href="{{ url('accounting/phase5/insights') }}" class="acct-ws-link {{ request()->is('accounting/phase5/insights*') ? 'active' : '' }}">
            <i class="bi bi-lightbulb"></i> Financial Insights
        </a>
        <a href="{{ url('accounting/phase5/review') }}" class="acct-ws-link {{ request()->is('accounting/phase5/review*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-check"></i> AI Review Center
        </a>
        <a href="{{ url('accounting/phase5/automation') }}" class="acct-ws-link {{ request()->is('accounting/phase5/automation') && !request()->is('accounting/phase5/automation/history') ? 'active' : '' }}">
            <i class="bi bi-diagram-3"></i> Automation Rules
        </a>
        <a href="{{ url('accounting/phase5/automation/history') }}" class="acct-ws-link {{ request()->is('accounting/phase5/automation/history') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Automation History
        </a>
        @if($canManage)
        <a href="{{ url('accounting/phase5/settings') }}" class="acct-ws-link {{ request()->is('accounting/phase5/settings*') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i> AI Settings
        </a>
        @endif

        <div class="acct-ws-section">Phase 4 Analytics</div>
        <a href="{{ url('accounting/phase4/financial-dashboard') }}" class="acct-ws-link {{ request()->is('accounting/phase4/financial-dashboard*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line"></i> Financial Dashboard
        </a>
        <a href="{{ url('accounting/phase4/statements/profit-and-loss') }}" class="acct-ws-link {{ request()->is('accounting/phase4/statements*') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i> Statements (comparative)
        </a>
        <a href="{{ url('accounting/phase4/custom-reports') }}" class="acct-ws-link {{ request()->is('accounting/phase4/custom-reports*') ? 'active' : '' }}">
            <i class="bi bi-bookmark-star"></i> Saved Reports
        </a>
        <a href="{{ url('accounting/phase4/cash-forecast') }}" class="acct-ws-link {{ request()->is('accounting/phase4/cash-forecast*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Cash Forecast
        </a>
        <a href="{{ url('accounting/phase4/kpi-analytics') }}" class="acct-ws-link {{ request()->is('accounting/phase4/kpi-analytics*') ? 'active' : '' }}">
            <i class="bi bi-speedometer"></i> KPI Analytics
        </a>
        <a href="{{ url('accounting/phase4/tax') }}" class="acct-ws-link {{ request()->is('accounting/phase4/tax*') ? 'active' : '' }}">
            <i class="bi bi-percent"></i> Tax Management
        </a>
        <a href="{{ url('accounting/phase4/year-end') }}" class="acct-ws-link {{ request()->is('accounting/phase4/year-end*') ? 'active' : '' }}">
            <i class="bi bi-calendar2-check"></i> Year-End Closing
        </a>
        <a href="{{ url('accounting/phase4/scheduled-reports') }}" class="acct-ws-link {{ request()->is('accounting/phase4/scheduled-reports*') ? 'active' : '' }}">
            <i class="bi bi-clock"></i> Scheduled Reports
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
