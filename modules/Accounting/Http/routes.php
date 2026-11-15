<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'accounting.session', 'accounting.access'], 'prefix' => 'accounting', 'namespace' => 'Modules\Accounting\Http\Controllers'], function () {

    Route::get('/', function () {
        return redirect('accounting/dashboard');
    });

    Route::get('/install', 'InstallController@index');
    Route::post('/install', 'InstallController@install');
    Route::get('/install/uninstall', 'InstallController@uninstall');
    Route::get('/install/update', 'InstallController@update');

    /** Dashboard Routes */
    Route::prefix('dashboard')->group(function () {
        Route::get('/', 'DashboardController@index');
        Route::get('get_totals', 'DashboardController@get_totals');
    });

    /**Accounting routes */
    Route::get('trial_balance', 'AccountingController@trial_balance');
    Route::get('ledger', 'AccountingController@ledger');
    Route::get('balance_sheet', 'AccountingController@balance_sheet');
    Route::get('profit_and_loss', 'AccountingController@profit_and_loss');
    Route::get('cash_flow', 'AccountingController@cash_flow');

    //chart of accounts
    Route::prefix('chart_of_account')->group(function () {
        Route::get('/', 'ChartOfAccountController@index');
        Route::get('get_chart_of_accounts', 'ChartOfAccountController@get_chart_of_accounts');
        Route::get('create', 'ChartOfAccountController@create');
        Route::post('store', 'ChartOfAccountController@store');
        Route::get('{id}/show', 'ChartOfAccountController@show');
        Route::get('{id}/edit', 'ChartOfAccountController@edit');
        Route::post('{id}/update', 'ChartOfAccountController@update');
        Route::get('{id}/destroy', 'ChartOfAccountController@destroy');
        Route::get('export', 'ChartOfAccountController@export');
    });

    //journal entries
    Route::prefix('journal_entry')->group(function () {
        Route::get('/', 'JournalEntryController@index');
        Route::get('get_journal_entries', 'JournalEntryController@get_journal_entries');
        Route::get('create', 'JournalEntryController@create');
        Route::post('store', 'JournalEntryController@store');
        Route::get('{id}/show', 'JournalEntryController@show');
        Route::get('{id}/edit', 'JournalEntryController@edit');
        Route::get('{id}/reverse', 'JournalEntryController@reverse');
        Route::post('{id}/update', 'JournalEntryController@update');
        Route::get('{id}/destroy', 'JournalEntryController@destroy');
    });

    //Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('sales', 'AccountingTransactionController@sales');
        Route::get('expenses', 'AccountingTransactionController@expenses');
        Route::get('purchases', 'AccountingTransactionController@purchases');
        Route::get('inventory', 'AccountingTransactionController@inventory');
        Route::post('map_to_chart_of_account', 'AccountingTransactionController@map_to_chart_of_account');
    });

    //Transfers
    Route::prefix('transfers')->group(function () {
        Route::get('/', 'AccountingController@transfers');
        Route::get('create', 'AccountingController@create_transfer');
        Route::post('store', 'AccountingController@store_transfer');
    });

    //Budget
    Route::prefix('budget')->group(function () {
        Route::get('/', 'BudgetController@index');
        Route::post('update_monthly_budget', 'BudgetController@update_monthly_budget');
        Route::post('update_quarterly_budget', 'BudgetController@update_quarterly_budget');
        Route::post('update_yearly_budget', 'BudgetController@update_yearly_budget');
        Route::post('store_financial_year_start', 'BudgetController@store_financial_year_start');
    });

    //Reconcile
    Route::prefix('reconcile')->group(function () {
        Route::get('/', 'ReconcileController@index');
        Route::post('start', 'ReconcileController@start_reconcile');
        Route::post('store', 'ReconcileController@store_reconcile');
        Route::post('undo', 'ReconcileController@undo_reconcile');
    });

    // Phase 2 — Manual multi-line journals
    Route::prefix('manual-journals')->group(function () {
        Route::get('/', 'ManualJournalController@index');
        Route::get('approvals', 'ManualJournalController@approvalQueue');
        Route::get('create', 'ManualJournalController@create');
        Route::post('/', 'ManualJournalController@store');
        Route::get('{id}', 'ManualJournalController@show');
        Route::get('{id}/edit', 'ManualJournalController@edit');
        Route::post('{id}', 'ManualJournalController@update');
        Route::post('{id}/submit', 'ManualJournalController@submit');
        Route::post('{id}/approve', 'ManualJournalController@approve');
        Route::post('{id}/reject', 'ManualJournalController@reject');
        Route::post('{id}/post', 'ManualJournalController@post');
        Route::post('{id}/reverse', 'ManualJournalController@reverse');
    });

    // Phase 2 — Account register
    Route::prefix('account-register')->group(function () {
        Route::get('/', 'AccountRegisterController@index');
        Route::get('export', 'AccountRegisterController@exportCsv');
        Route::get('{accountId}', 'AccountRegisterController@show');
    });

    // Phase 2 — Periods
    Route::prefix('periods')->group(function () {
        Route::get('/', 'AccountingPeriodController@index');
        Route::post('/', 'AccountingPeriodController@store');
        Route::post('{id}/close', 'AccountingPeriodController@close');
        Route::post('{id}/reopen', 'AccountingPeriodController@reopen');
    });

    // Phase 2 — Bank statement imports + matching
    Route::prefix('bank-statements')->group(function () {
        Route::get('/', 'BankStatementController@index');
        Route::post('/', 'BankStatementController@store');
        Route::get('{id}', 'BankStatementController@show');
    });
    Route::prefix('bank-matching')->group(function () {
        Route::get('/', 'BankMatchingController@index');
        Route::post('{id}/confirm', 'BankMatchingController@confirm');
        Route::post('{id}/unmatch', 'BankMatchingController@unmatch');
        Route::post('{id}/exclude', 'BankMatchingController@exclude');
    });
    Route::get('bank-reconciliation-report', 'AccountingStatementController@bankReconForm');
    Route::get('bank-reconciliation-report/pdf', 'AccountingStatementController@bankReconPdf');

    // Phase 2 — AR / AP
    Route::get('receivables/customer-ledger', 'CustomerLedgerController@index');
    Route::get('receivables/payment-allocation', 'PaymentAllocationController@customerIndex');
    Route::post('receivables/payment-allocation', 'PaymentAllocationController@customerStore');
    Route::get('receivables/customer-statement', 'AccountingStatementController@customerForm');
    Route::get('receivables/customer-statement/pdf', 'AccountingStatementController@customerPdf');

    Route::get('payables/supplier-ledger', 'SupplierLedgerController@index');
    Route::get('payables/payment-allocation', 'PaymentAllocationController@supplierIndex');
    Route::post('payables/payment-allocation', 'PaymentAllocationController@supplierStore');
    Route::get('payables/supplier-statement', 'AccountingStatementController@supplierForm');
    Route::get('payables/supplier-statement/pdf', 'AccountingStatementController@supplierPdf');

    // Phase 2 — Recurring (journals / invoices / bills)
    Route::prefix('recurring-journals')->group(function () {
        Route::get('/', 'RecurringJournalController@index');
        Route::get('history', 'RecurringJournalController@history');
        Route::get('create', 'RecurringJournalController@create');
        Route::post('/', 'RecurringJournalController@store');
        Route::post('{id}/pause', 'RecurringJournalController@pause');
        Route::post('{id}/resume', 'RecurringJournalController@resume');
        Route::post('{id}/run', 'RecurringJournalController@runNow');
    });

    Route::get('audit-log', 'AccountingAuditController@index');

    // Phase 3 — Fixed assets
    Route::prefix('fixed-assets')->group(function () {
        Route::get('/', 'FixedAssetController@index');
        Route::get('create', 'FixedAssetController@create');
        Route::post('/', 'FixedAssetController@store');
        Route::get('{id}', 'FixedAssetController@show')->whereNumber('id');
        Route::get('{id}/edit', 'FixedAssetController@edit')->whereNumber('id');
        Route::post('{id}', 'FixedAssetController@update')->whereNumber('id');
        Route::post('{id}/submit', 'FixedAssetController@submit')->whereNumber('id');
        Route::post('{id}/approve', 'FixedAssetController@approve')->whereNumber('id');
        Route::post('{id}/capitalize', 'FixedAssetController@capitalize')->whereNumber('id');
        Route::post('{id}/transfer', 'FixedAssetController@transfer')->whereNumber('id');
        Route::post('{id}/deactivate', 'FixedAssetController@deactivate')->whereNumber('id');
    });
    Route::prefix('fixed-asset-categories')->group(function () {
        Route::get('/', 'FixedAssetCategoryController@index');
        Route::post('/', 'FixedAssetCategoryController@store');
        Route::post('{id}', 'FixedAssetCategoryController@update')->whereNumber('id');
    });
    Route::prefix('depreciation')->group(function () {
        Route::get('runs', 'DepreciationController@runs');
        Route::get('preview', 'DepreciationController@preview');
        Route::post('runs', 'DepreciationController@postRun');
        Route::post('runs/{id}/reverse', 'DepreciationController@reverseRun')->whereNumber('id');
        Route::get('assets/{assetId}/schedule', 'DepreciationController@assetSchedule')->whereNumber('assetId');
    });
    Route::get('asset-disposals', 'AssetDisposalController@create');
    Route::post('asset-disposals', 'AssetDisposalController@store');

    // Phase 3 — Loans
    Route::prefix('loans')->group(function () {
        Route::get('/', 'LoanController@index');
        Route::get('create', 'LoanController@create');
        Route::post('/', 'LoanController@store');
        Route::get('{id}', 'LoanController@show')->whereNumber('id');
        Route::post('{id}/schedule', 'LoanController@generateSchedule')->whereNumber('id');
        Route::post('{id}/disburse', 'LoanController@disburse')->whereNumber('id');
        Route::get('{id}/payment', 'LoanController@paymentForm')->whereNumber('id');
        Route::post('{id}/payment', 'LoanController@recordPayment')->whereNumber('id');
        Route::get('{id}/statement', 'LoanController@statement')->whereNumber('id');
    });

    // Phase 3 — Cost accounting
    Route::prefix('cost-centers')->group(function () {
        Route::get('/', 'CostCenterController@index');
        Route::get('create', 'CostCenterController@create');
        Route::post('/', 'CostCenterController@store');
        Route::get('{id}/edit', 'CostCenterController@edit')->whereNumber('id');
        Route::post('{id}', 'CostCenterController@update')->whereNumber('id');
    });
    Route::prefix('cost-allocations')->group(function () {
        Route::get('/', 'CostCenterController@allocationsIndex');
        Route::get('create', 'CostCenterController@allocationsCreate');
        Route::post('/', 'CostCenterController@allocationsStore');
    });

    // Phase 3 — Job & manufacturing accounting
    Route::get('job-costs', 'JobCostController@index');
    Route::get('job-costs/{id}', 'JobCostController@show')->whereNumber('id');
    Route::get('manufacturing-accounting', 'ManufacturingAccountingController@summary');

    // Phase 3 — Advanced budgeting
    Route::prefix('budget-versions')->group(function () {
        Route::get('/', 'AdvancedBudgetController@index');
        Route::get('create', 'AdvancedBudgetController@create');
        Route::post('/', 'AdvancedBudgetController@store');
        Route::get('vs-actual', 'AdvancedBudgetController@vsActual');
        Route::get('{id}', 'AdvancedBudgetController@show')->whereNumber('id');
        Route::post('{id}/submit', 'AdvancedBudgetController@submit')->whereNumber('id');
        Route::post('{id}/approve', 'AdvancedBudgetController@approve')->whereNumber('id');
        Route::post('{id}/activate', 'AdvancedBudgetController@activate')->whereNumber('id');
        Route::post('{id}/revise', 'AdvancedBudgetController@revise')->whereNumber('id');
    });

    //Settings
    Route::prefix('settings')->group(function () {

        Route::prefix('account_subtypes')->group(function () {
            Route::get('/', 'AccountingSettingsController@account_subtypes');
            Route::post('store', 'AccountingSettingsController@store_account_subtypes');
            Route::get('{id}/edit', 'AccountingSettingsController@edit_account_subtypes');
            Route::post('{id}/update', 'AccountingSettingsController@update_account_subtypes');
            Route::delete('{id}/destroy', 'AccountingSettingsController@destroy_account_subtype');
        });

        Route::prefix('detail_types')->group(function () {
            Route::get('/', 'AccountingSettingsController@detail_types');
            Route::post('store', 'AccountingSettingsController@store_detail_types');
            Route::get('{id}/edit', 'AccountingSettingsController@edit_detail_types');
            Route::post('{id}/update', 'AccountingSettingsController@update_detail_types');
            Route::delete('{id}/destroy', 'AccountingSettingsController@destroy_detail_type');
        });

    });
});

//Media
Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'media', 'namespace' => 'Modules\Accounting\Http\Controllers'], function () {
    Route::post('{id}/download', 'MediaController@download')->name('media.download');
    Route::delete('{id}/delete', 'MediaController@delete')->name('media.delete');
});


Route::group(['middleware' => ['web', 'auth', 'accounting.session', 'accounting.access'], 'prefix' => 'report', 'namespace' => 'Modules\Accounting\Http\Controllers'], function () {
    Route::get('accounting', 'ReportController@index');

    //Business Overview
    Route::get('accounting/balance_sheet', 'ReportController@balance_sheet');
    Route::get('accounting/cash_flow', 'ReportController@cash_flow');
    Route::get('accounting/profit_and_loss', 'ReportController@profit_and_loss');

    // Bookkeeping
    Route::get('accounting/ledger', 'ReportController@ledger');
    Route::get('accounting/trial_balance', 'ReportController@trial_balance');
    Route::get('accounting/journal', 'ReportController@journal');

    // Budget
    Route::get('accounting/budget_overview', 'ReportController@budget_overview');

    // Who owes you
    Route::get('accounting/accounts_receivable_ageing_summary', 'ReportController@accounts_receivable_ageing_summary');
    Route::get('accounting/accounts_receivable_ageing_detail', 'ReportController@accounts_receivable_ageing_detail');

    // What you owe
    Route::get('accounting/accounts_payable_ageing_summary', 'ReportController@accounts_payable_ageing_summary');
    Route::get('accounting/accounts_payable_ageing_detail', 'ReportController@accounts_payable_ageing_detail');
});
