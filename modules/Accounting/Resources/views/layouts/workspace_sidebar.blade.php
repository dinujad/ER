@php
    $u = auth()->user();
    $canView = $u && ($u->isSystemAdmin() || $u->can_access('accounting.view'));
    $canManage = $u && ($u->isSystemAdmin() || $u->can_access('accounting.manage'));
    $biz = session('business.name') ?? config('app.name');
    $path = request()->path();
    $is = fn (string $pattern) => request()->is($pattern);
    $active = function (string ...$patterns) use ($is) {
        foreach ($patterns as $p) {
            if ($is($p)) {
                return 'active';
            }
        }
        return '';
    };
    $groupOpen = function (array $patterns) use ($is) {
        foreach ($patterns as $p) {
            if ($is($p)) {
                return 'open';
            }
        }
        return '';
    };
@endphp

<aside class="acct-ws-sidebar" id="acctWorkspaceSidebar" aria-label="Accounting navigation">
    <div class="acct-ws-brand">
        <div class="acct-ws-brand-mark" aria-hidden="true"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div class="acct-ws-brand-text">
            <div class="acct-ws-brand-title">Accounting</div>
            <div class="acct-ws-brand-sub">{{ \Illuminate\Support\Str::limit($biz, 28) }} · LKR</div>
        </div>
    </div>

    @if($canView)
    <div class="acct-ws-nav-search">
        <label class="visually-hidden" for="acctNavSearch">Search navigation</label>
        <input type="search" id="acctNavSearch" placeholder="Search menu…" autocomplete="off">
    </div>

    <nav class="acct-ws-nav" id="acctWsNav">
        {{-- Overview --}}
        <div class="acct-ws-group open {{ $groupOpen(['accounting/dashboard*','accounting/phase4/financial-dashboard*','accounting/phase4/kpi*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="true" title="Overview"><i class="bi bi-house-door group-ico" aria-hidden="true"></i><span class="label">Overview</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/dashboard') }}" class="acct-ws-link {{ $active('accounting/dashboard*') }}" data-label="Dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                <a href="{{ url('accounting/phase4/financial-dashboard') }}" class="acct-ws-link {{ $active('accounting/phase4/financial-dashboard*') }}" data-label="Financial Overview"><i class="bi bi-bar-chart-line"></i><span>Financial Overview</span></a>
                <a href="{{ url('accounting/phase4/kpi-analytics') }}" class="acct-ws-link {{ $active('accounting/phase4/kpi*') }}" data-label="KPI Analytics"><i class="bi bi-speedometer"></i><span>KPI Analytics</span></a>
            </div>
        </div>

        {{-- Accounting Core --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/phase6/account-transactions*','accounting/phase6/recode*','accounting/phase6/journal*','accounting/chart_of_account*','accounting/account-register*','accounting/journal_entry*','accounting/manual-journals*','accounting/recurring-journals*','accounting/transactions*','accounting/transfers*','report/accounting/ledger*','report/accounting/journal*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Accounting"><i class="bi bi-journal-text group-ico" aria-hidden="true"></i><span class="label">Accounting</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/chart_of_account') }}" class="acct-ws-link {{ $active('accounting/chart_of_account*') }}" data-label="Chart of Accounts"><i class="bi bi-diagram-3"></i><span>Chart of Accounts</span></a>
                <a href="{{ url('accounting/account-register') }}" class="acct-ws-link {{ $active('accounting/account-register*') }}" data-label="Account Register"><i class="bi bi-list-ol"></i><span>Account Register</span></a>
                <a href="{{ url('report/accounting/ledger') }}" class="acct-ws-link {{ $active('report/accounting/ledger*') }}" data-label="General Ledger"><i class="bi bi-book"></i><span>General Ledger</span></a>
                <a href="{{ url('accounting/phase6/account-transactions') }}" class="acct-ws-link {{ $active('accounting/phase6/account-transactions*') }}" data-label="Account Transactions"><i class="bi bi-search"></i><span>Account Transactions</span></a>
                <a href="{{ url('accounting/phase6/recode') }}" class="acct-ws-link {{ $active('accounting/phase6/recode*') }}" data-label="Find & Recode"><i class="bi bi-arrow-left-right"></i><span>Find &amp; Recode</span></a>
                <a href="{{ url('accounting/journal_entry') }}" class="acct-ws-link {{ $active('accounting/journal_entry*') }}" data-label="Journal Entries"><i class="bi bi-journal-text"></i><span>Journal Entries</span></a>
                <a href="{{ url('accounting/manual-journals') }}" class="acct-ws-link {{ request()->is('accounting/manual-journals*') && !request()->is('accounting/manual-journals/approvals') ? 'active' : '' }}" data-label="Multi-line Journals"><i class="bi bi-journal-plus"></i><span>Multi-line Journals</span></a>
                <a href="{{ url('accounting/manual-journals/create') }}" class="acct-ws-link" data-label="New Journal"><i class="bi bi-plus-square"></i><span>New Journal</span></a>
                <a href="{{ url('accounting/manual-journals/approvals') }}" class="acct-ws-link {{ $active('accounting/manual-journals/approvals') }}" data-label="Journal Approvals"><i class="bi bi-check2-square"></i><span>Journal Approvals</span></a>
                <a href="{{ url('accounting/recurring-journals?document_type=journal') }}" class="acct-ws-link {{ $is('accounting/recurring-journals*') && request('document_type','journal')==='journal' ? 'active' : '' }}" data-label="Recurring Journals"><i class="bi bi-arrow-repeat"></i><span>Recurring Journals</span></a>
                <a href="{{ url('accounting/transactions/sales') }}" class="acct-ws-link {{ $active('accounting/transactions*') }}" data-label="ERP Transactions"><i class="bi bi-arrow-left-right"></i><span>ERP Transactions</span></a>
                <a href="{{ url('accounting/transfers') }}" class="acct-ws-link {{ $active('accounting/transfers*') }}" data-label="Transfers"><i class="bi bi-shuffle"></i><span>Transfers</span></a>
            </div>
        </div>

        {{-- Banking --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/banking*','accounting/phase7/reconcile*','accounting/reconcile*','accounting/bank-statements*','accounting/bank-matching*','accounting/bank-reconciliation*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Banking & Cash"><i class="bi bi-bank group-ico" aria-hidden="true"></i><span class="label">Banking &amp; Cash</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/banking') }}" class="acct-ws-link {{ $active('accounting/banking*') }}" data-label="Bank Accounts"><i class="bi bi-bank2"></i><span>Bank Accounts</span></a>
                <a href="{{ url('accounting/phase7/reconcile') }}" class="acct-ws-link {{ $active('accounting/phase7/reconcile*') }}" data-label="Review Transactions"><i class="bi bi-check2-square"></i><span>Review Transactions</span></a>
                <a href="{{ url('accounting/reconcile') }}" class="acct-ws-link {{ $active('accounting/reconcile*') }}" data-label="Bank Reconciliation"><i class="bi bi-bank"></i><span>Reconciliation (classic)</span></a>
                <a href="{{ url('accounting/bank-statements') }}" class="acct-ws-link {{ $active('accounting/bank-statements*') }}" data-label="Statement Imports"><i class="bi bi-file-earmark-spreadsheet"></i><span>Statement Imports</span></a>
                <a href="{{ url('accounting/bank-matching') }}" class="acct-ws-link {{ $active('accounting/bank-matching*') }}" data-label="Bank Matching"><i class="bi bi-link-45deg"></i><span>Transaction Matching</span></a>
                <a href="{{ url('accounting/bank-reconciliation-report') }}" class="acct-ws-link {{ $active('accounting/bank-reconciliation*') }}" data-label="Recon Report"><i class="bi bi-filetype-pdf"></i><span>Recon Report PDF</span></a>
            </div>
        </div>

        {{-- Sales / AR --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/receivables*','report/accounting/accounts_receivable*','accounting/phase6/invoices*','accounting/phase6/receivables*','accounting/phase6/credit-notes*','accounting/phase7/reminders*','accounting/phase7/recurring-queue*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Sales Accounting"><i class="bi bi-people group-ico" aria-hidden="true"></i><span class="label">Sales Accounting</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/phase6/invoices') }}" class="acct-ws-link {{ $active('accounting/phase6/invoices*') }}" data-label="Sales Invoices"><i class="bi bi-receipt-cutoff"></i><span>Sales Invoices</span></a>
                <a href="{{ url('accounting/phase6/receivables/overdue') }}" class="acct-ws-link {{ $active('accounting/phase6/receivables*') }}" data-label="Overdue Invoices"><i class="bi bi-alarm"></i><span>Overdue Invoices</span></a>
                <a href="{{ url('accounting/phase6/credit-notes') }}" class="acct-ws-link {{ $active('accounting/phase6/credit-notes*') }}" data-label="Credit Notes"><i class="bi bi-file-earmark-minus"></i><span>Credit Notes</span></a>
                <a href="{{ url('accounting/phase7/reminders') }}" class="acct-ws-link {{ $active('accounting/phase7/reminders*') }}" data-label="Reminder History"><i class="bi bi-chat-dots"></i><span>Reminder History</span></a>
                <a href="{{ url('accounting/phase7/recurring-queue') }}" class="acct-ws-link {{ $active('accounting/phase7/recurring-queue*') }}" data-label="Recurring Queue"><i class="bi bi-arrow-repeat"></i><span>Recurring Queue</span></a>
                <a href="{{ url('accounting/receivables/customer-ledger') }}" class="acct-ws-link {{ $active('accounting/receivables/customer-ledger*') }}" data-label="Customer Ledger"><i class="bi bi-people"></i><span>Customer Ledger</span></a>
                <a href="{{ url('accounting/receivables/payment-allocation') }}" class="acct-ws-link {{ $active('accounting/receivables/payment-allocation*') }}" data-label="Payment Allocations"><i class="bi bi-cash-coin"></i><span>Payment Allocations</span></a>
                <a href="{{ url('accounting/receivables/customer-statement') }}" class="acct-ws-link {{ $active('accounting/receivables/customer-statement*') }}" data-label="Customer Statements"><i class="bi bi-filetype-pdf"></i><span>Customer Statements</span></a>
                <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="acct-ws-link {{ $active('report/accounting/accounts_receivable*') }}" data-label="AR Aging"><i class="bi bi-calendar3"></i><span>AR Aging</span></a>
                <a href="{{ url('accounting/recurring-journals?document_type=invoice') }}" class="acct-ws-link {{ request('document_type')==='invoice' ? 'active' : '' }}" data-label="Recurring Invoices"><i class="bi bi-receipt"></i><span>Recurring Invoices</span></a>
            </div>
        </div>

        {{-- Purchases / AP --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/payables*','report/accounting/accounts_payable*','accounting/phase6/bills*','accounting/phase7/payment-runs*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Purchase Accounting"><i class="bi bi-building group-ico" aria-hidden="true"></i><span class="label">Purchase Accounting</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/phase6/bills') }}" class="acct-ws-link {{ $is('accounting/phase6/bills') || $is('accounting/phase6/bills/[0-9]*') ? 'active' : '' }}" data-label="Bills"><i class="bi bi-file-earmark-text"></i><span>Bills</span></a>
                <a href="{{ url('accounting/phase6/bills/payment-run') }}" class="acct-ws-link {{ $active('accounting/phase6/bills/payment-run*') }}" data-label="Pay Bills"><i class="bi bi-cash-stack"></i><span>Pay Bills</span></a>
                <a href="{{ url('accounting/phase7/payment-runs') }}" class="acct-ws-link {{ $active('accounting/phase7/payment-runs*') }}" data-label="Payment Runs"><i class="bi bi-collection"></i><span>Payment Runs</span></a>
                <a href="{{ url('accounting/payables/supplier-ledger') }}" class="acct-ws-link {{ $active('accounting/payables/supplier-ledger*') }}" data-label="Supplier Ledger"><i class="bi bi-building"></i><span>Supplier Ledger</span></a>
                <a href="{{ url('accounting/payables/payment-allocation') }}" class="acct-ws-link {{ $active('accounting/payables/payment-allocation*') }}" data-label="Supplier Payments"><i class="bi bi-cash-coin"></i><span>Payment Allocations</span></a>
                <a href="{{ url('accounting/payables/supplier-statement') }}" class="acct-ws-link {{ $active('accounting/payables/supplier-statement*') }}" data-label="Supplier Statements"><i class="bi bi-filetype-pdf"></i><span>Supplier Statements</span></a>
                <a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}" class="acct-ws-link {{ $active('report/accounting/accounts_payable*') }}" data-label="AP Aging"><i class="bi bi-calendar3"></i><span>AP Aging</span></a>
                <a href="{{ url('accounting/recurring-journals?document_type=bill') }}" class="acct-ws-link {{ request('document_type')==='bill' ? 'active' : '' }}" data-label="Recurring Bills"><i class="bi bi-file-earmark-ruled"></i><span>Recurring Bills</span></a>
            </div>
        </div>

        {{-- Assets & Loans --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/fixed-assets*','accounting/fixed-asset*','accounting/depreciation*','accounting/asset-disposals*','accounting/purchase-capitalization*','accounting/loans*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Assets & Liabilities"><i class="bi bi-boxes group-ico" aria-hidden="true"></i><span class="label">Assets &amp; Liabilities</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/fixed-assets') }}" class="acct-ws-link {{ $active('accounting/fixed-assets') || $is('accounting/fixed-assets/*') && !$is('accounting/fixed-assets/reports*') ? 'active' : '' }}" data-label="Fixed Assets"><i class="bi bi-building"></i><span>Asset Register</span></a>
                <a href="{{ url('accounting/fixed-asset-categories') }}" class="acct-ws-link {{ $active('accounting/fixed-asset-categories*') }}" data-label="Asset Categories"><i class="bi bi-tags"></i><span>Categories</span></a>
                <a href="{{ url('accounting/purchase-capitalization') }}" class="acct-ws-link {{ $active('accounting/purchase-capitalization*') }}" data-label="Capitalization"><i class="bi bi-bag-check"></i><span>Purchase Capitalization</span></a>
                <a href="{{ url('accounting/depreciation/runs') }}" class="acct-ws-link {{ $active('accounting/depreciation*') }}" data-label="Depreciation"><i class="bi bi-graph-down"></i><span>Depreciation Runs</span></a>
                <a href="{{ url('accounting/asset-disposals') }}" class="acct-ws-link {{ $active('accounting/asset-disposals*') }}" data-label="Disposal"><i class="bi bi-trash3"></i><span>Asset Disposal</span></a>
                <a href="{{ url('accounting/fixed-assets/reports/register/pdf') }}" class="acct-ws-link {{ $active('accounting/fixed-assets/reports*') }}" data-label="Asset Reports"><i class="bi bi-file-earmark-spreadsheet"></i><span>Asset Reports</span></a>
                <a href="{{ url('accounting/loans') }}" class="acct-ws-link {{ $active('accounting/loans*') }}" data-label="Loan Register"><i class="bi bi-bank2"></i><span>Loan Register</span></a>
            </div>
        </div>

        {{-- Cost & Manufacturing --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/cost-centers*','accounting/cost-allocations*','accounting/cost-center-reports*','accounting/job-costs*','accounting/manufacturing*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Cost Accounting"><i class="bi bi-diagram-2 group-ico" aria-hidden="true"></i><span class="label">Cost Accounting</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/cost-centers') }}" class="acct-ws-link {{ $active('accounting/cost-centers*') }}" data-label="Cost Centers"><i class="bi bi-diagram-2"></i><span>Cost Centers</span></a>
                <a href="{{ url('accounting/cost-allocations') }}" class="acct-ws-link {{ $active('accounting/cost-allocations*') }}" data-label="Allocations"><i class="bi bi-pie-chart"></i><span>Allocations</span></a>
                <a href="{{ url('accounting/cost-center-reports/profit-and-loss') }}" class="acct-ws-link {{ $active('accounting/cost-center-reports*') }}" data-label="Cost Center P&L"><i class="bi bi-bar-chart"></i><span>Cost Center P&amp;L</span></a>
                <a href="{{ url('accounting/job-costs') }}" class="acct-ws-link {{ $active('accounting/job-costs*') }}" data-label="Job Costing"><i class="bi bi-clipboard-data"></i><span>Job Costing</span></a>
                <a href="{{ url('accounting/manufacturing-accounting') }}" class="acct-ws-link {{ $active('accounting/manufacturing-accounting*') }}" data-label="Manufacturing"><i class="bi bi-gear-wide-connected"></i><span>Production Costing</span></a>
                <a href="{{ url('accounting/manufacturing-overhead/rules') }}" class="acct-ws-link {{ $active('accounting/manufacturing-overhead*') }}" data-label="Overhead"><i class="bi bi-sliders"></i><span>Overhead Rules</span></a>
            </div>
        </div>

        {{-- Planning --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/budget*','accounting/phase4/cash-forecast*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Financial Planning"><i class="bi bi-calendar3 group-ico" aria-hidden="true"></i><span class="label">Financial Planning</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/budget') }}" class="acct-ws-link {{ $is('accounting/budget') || $is('accounting/budget/*') ? 'active' : '' }}" data-label="Budget"><i class="bi bi-calendar3"></i><span>Budget</span></a>
                <a href="{{ url('accounting/budget-versions') }}" class="acct-ws-link {{ $active('accounting/budget-versions*') && !$is('accounting/budget-versions/vs-actual*') ? 'active' : '' }}" data-label="Budget Versions"><i class="bi bi-collection"></i><span>Budget Versions</span></a>
                <a href="{{ url('accounting/budget-versions/vs-actual') }}" class="acct-ws-link {{ $active('accounting/budget-versions/vs-actual*') }}" data-label="Budget vs Actual"><i class="bi bi-bar-chart-steps"></i><span>Budget vs Actual</span></a>
                <a href="{{ url('accounting/phase4/cash-forecast') }}" class="acct-ws-link {{ $active('accounting/phase4/cash-forecast*') }}" data-label="Cash Forecast"><i class="bi bi-graph-up"></i><span>Cash Flow Forecast</span></a>
            </div>
        </div>

        {{-- Reports --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/phase4/statements*','accounting/phase4/custom*','accounting/phase4/scheduled*','report/accounting*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Financial Reports"><i class="bi bi-graph-up group-ico" aria-hidden="true"></i><span class="label">Financial Reports</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/phase4/statements/profit-and-loss') }}" class="acct-ws-link {{ $is('accounting/phase4/statements/profit-and-loss') ? 'active' : '' }}" data-label="Profit and Loss"><i class="bi bi-graph-up"></i><span>Profit &amp; Loss</span></a>
                <a href="{{ url('accounting/phase4/statements/balance-sheet') }}" class="acct-ws-link {{ $is('accounting/phase4/statements/balance-sheet') ? 'active' : '' }}" data-label="Balance Sheet"><i class="bi bi-pie-chart"></i><span>Balance Sheet</span></a>
                <a href="{{ url('accounting/phase4/statements/cash-flow') }}" class="acct-ws-link {{ $is('accounting/phase4/statements/cash-flow') ? 'active' : '' }}" data-label="Cash Flow"><i class="bi bi-cash-stack"></i><span>Cash Flow</span></a>
                <a href="{{ url('accounting/phase4/statements/trial-balance') }}" class="acct-ws-link {{ $is('accounting/phase4/statements/trial-balance') ? 'active' : '' }}" data-label="Trial Balance"><i class="bi bi-list-check"></i><span>Trial Balance</span></a>
                <a href="{{ url('accounting/phase4/statements/changes-in-equity') }}" class="acct-ws-link {{ $is('accounting/phase4/statements/changes-in-equity') ? 'active' : '' }}" data-label="Changes in Equity"><i class="bi bi-diagram-3"></i><span>Changes in Equity</span></a>
                <a href="{{ url('accounting/cost-center-reports/profit-and-loss') }}" class="acct-ws-link {{ $active('accounting/cost-center-reports*') }}" data-label="Cost Center P and L"><i class="bi bi-building"></i><span>Cost Center P&amp;L</span></a>
                <a href="{{ url('accounting/phase4/kpi-analytics') }}" class="acct-ws-link {{ $active('accounting/phase4/kpi*') }}" data-label="KPI Analytics"><i class="bi bi-speedometer2"></i><span>KPI Analytics</span></a>
                <a href="{{ url('accounting/phase4/custom-reports') }}" class="acct-ws-link {{ $active('accounting/phase4/custom-reports*') }}" data-label="Saved Reports"><i class="bi bi-bookmark-star"></i><span>Saved Reports</span></a>
                <a href="{{ url('accounting/phase4/scheduled-reports') }}" class="acct-ws-link {{ $active('accounting/phase4/scheduled-reports*') }}" data-label="Scheduled Reports"><i class="bi bi-clock"></i><span>Scheduled Reports</span></a>
                <a href="{{ url('report/accounting') }}" class="acct-ws-link {{ $is('report/accounting') ? 'active' : '' }}" data-label="Classic Reports"><i class="bi bi-grid"></i><span>Classic Reports</span></a>
            </div>
        </div>

        {{-- Tax & Closing --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/phase4/tax*','accounting/phase4/year-end*','accounting/periods*','accounting/audit-log*','accounting/phase6/vat-return*','accounting/phase6/tax-invoice-register*','accounting/phase7/close*','accounting/phase6/audit-trail*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Tax & Closing"><i class="bi bi-percent group-ico" aria-hidden="true"></i><span class="label">Tax &amp; Closing</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/phase6/vat-return') }}" class="acct-ws-link {{ $active('accounting/phase6/vat-return*') }}" data-label="VAT & SSCL Return"><i class="bi bi-percent"></i><span>VAT &amp; SSCL Return</span></a>
                <a href="{{ url('accounting/phase6/tax-invoice-register') }}" class="acct-ws-link {{ $active('accounting/phase6/tax-invoice-register*') }}" data-label="Tax Invoice Register"><i class="bi bi-list-ol"></i><span>Tax Invoice Register</span></a>
                <a href="{{ url('accounting/phase7/close') }}" class="acct-ws-link {{ $active('accounting/phase7/close*') }}" data-label="Month-end Close"><i class="bi bi-calendar-check"></i><span>Month-end Close</span></a>
                <a href="{{ url('accounting/phase4/tax') }}" class="acct-ws-link {{ $active('accounting/phase4/tax*') }}" data-label="Tax"><i class="bi bi-sliders"></i><span>Tax Codes &amp; Periods</span></a>
                <a href="{{ url('accounting/periods') }}" class="acct-ws-link {{ $active('accounting/periods*') }}" data-label="Periods"><i class="bi bi-lock"></i><span>Accounting Periods</span></a>
                <a href="{{ url('accounting/phase4/year-end') }}" class="acct-ws-link {{ $active('accounting/phase4/year-end*') }}" data-label="Year End"><i class="bi bi-calendar2-check"></i><span>Year-End Closing</span></a>
                <a href="{{ url('accounting/phase6/audit-trail') }}" class="acct-ws-link {{ $active('accounting/phase6/audit-trail*') }}" data-label="Audit Trail"><i class="bi bi-person-lines-fill"></i><span>Audit Trail (who did what)</span></a>
                <a href="{{ url('accounting/audit-log') }}" class="acct-ws-link {{ $active('accounting/audit-log*') }}" data-label="Audit Log"><i class="bi bi-shield-check"></i><span>Accounting Audit Log</span></a>
            </div>
        </div>

        {{-- AI --}}
        <div class="acct-ws-group {{ $groupOpen(['accounting/phase5*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="AI & Automation"><i class="bi bi-robot group-ico" aria-hidden="true"></i><span class="label">AI &amp; Automation</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/phase5/assistant') }}" class="acct-ws-link {{ $active('accounting/phase5/assistant*') }}" data-label="AI Assistant"><i class="bi bi-robot"></i><span>AI Accounting Assistant</span></a>
                <a href="{{ url('accounting/phase5/ocr') }}" class="acct-ws-link {{ $active('accounting/phase5/ocr*') }}" data-label="OCR"><i class="bi bi-file-earmark-image"></i><span>Smart Document Capture</span></a>
                <a href="{{ url('accounting/phase5/categorization') }}" class="acct-ws-link {{ $active('accounting/phase5/categorization*') }}" data-label="Categorization"><i class="bi bi-tags"></i><span>Categorization</span></a>
                <a href="{{ url('accounting/phase5/alerts') }}" class="acct-ws-link {{ $active('accounting/phase5/alerts*') }}" data-label="Alerts"><i class="bi bi-exclamation-triangle"></i><span>Financial Alerts</span></a>
                <a href="{{ url('accounting/phase5/insights') }}" class="acct-ws-link {{ $active('accounting/phase5/insights*') }}" data-label="Insights"><i class="bi bi-lightbulb"></i><span>Financial Insights</span></a>
                <a href="{{ url('accounting/phase5/review') }}" class="acct-ws-link {{ $active('accounting/phase5/review*') }}" data-label="Review Center"><i class="bi bi-clipboard-check"></i><span>AI Review Center</span></a>
                <a href="{{ url('accounting/phase5/automation') }}" class="acct-ws-link {{ $is('accounting/phase5/automation') ? 'active' : '' }}" data-label="Automation"><i class="bi bi-diagram-3"></i><span>Automation Rules</span></a>
                <a href="{{ url('accounting/phase5/automation/history') }}" class="acct-ws-link {{ $active('accounting/phase5/automation/history') }}" data-label="Automation History"><i class="bi bi-clock-history"></i><span>Automation History</span></a>
                @if($canManage)
                <a href="{{ url('accounting/phase5/settings') }}" class="acct-ws-link {{ $active('accounting/phase5/settings*') }}" data-label="AI Settings"><i class="bi bi-sliders"></i><span>AI Settings</span></a>
                @endif
            </div>
        </div>

        @if($canManage)
        <div class="acct-ws-group {{ $groupOpen(['accounting/settings*']) }}" data-group>
            <button type="button" class="acct-ws-group-btn" aria-expanded="false" title="Settings"><i class="bi bi-gear group-ico" aria-hidden="true"></i><span class="label">Settings</span><i class="bi bi-chevron-right chev"></i></button>
            <div class="acct-ws-group-body">
                <a href="{{ url('accounting/settings/account_subtypes') }}" class="acct-ws-link {{ $active('accounting/settings*') }}" data-label="Account Settings"><i class="bi bi-gear"></i><span>Account Settings</span></a>
            </div>
        </div>
        @endif
    </nav>
    @endif

    <div class="acct-ws-sidebar-foot">
        <button type="button" class="acct-ws-collapse-btn d-none d-lg-flex" id="acctWsCollapseBtn" aria-label="Collapse sidebar" title="Compact sidebar">
            <i class="bi bi-layout-sidebar-inset"></i><span class="d-acct-collapse-label">Compact</span>
        </button>
        <a href="{{ url('/erp/dashboard') }}" class="acct-ws-back" title="Back to ERP"><i class="bi bi-arrow-left-circle"></i><span>Back to ERP</span></a>
    </div>
</aside>
<div class="acct-ws-backdrop" id="acctWorkspaceBackdrop"></div>
