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

    /** Legacy report aliases → workspace report routes */
    Route::get('trial_balance', fn () => redirect('/report/accounting/trial_balance'));
    Route::get('ledger', fn () => redirect('/report/accounting/ledger'));
    Route::get('balance_sheet', fn () => redirect('/report/accounting/balance_sheet'));
    Route::get('profit_and_loss', fn () => redirect('/report/accounting/profit_and_loss'));
    Route::get('cash_flow', fn () => redirect('/report/accounting/cash_flow'));

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
        Route::get('reports/register/{format?}', 'FixedAssetExportController@register')->where('format', 'pdf|xlsx|xls|csv');
        Route::get('reports/disposals/{format?}', 'FixedAssetExportController@disposals')->where('format', 'pdf|xlsx|xls|csv');
        Route::get('{id}', 'FixedAssetController@show')->whereNumber('id');
        Route::get('{id}/edit', 'FixedAssetController@edit')->whereNumber('id');
        Route::post('{id}', 'FixedAssetController@update')->whereNumber('id');
        Route::post('{id}/submit', 'FixedAssetController@submit')->whereNumber('id');
        Route::post('{id}/approve', 'FixedAssetController@approve')->whereNumber('id');
        Route::post('{id}/capitalize', 'FixedAssetController@capitalize')->whereNumber('id');
        Route::post('{id}/transfer', 'FixedAssetController@transfer')->whereNumber('id');
        Route::post('{id}/deactivate', 'FixedAssetController@deactivate')->whereNumber('id');
        Route::post('{id}/attachments', 'FixedAssetAttachmentController@store')->whereNumber('id');
        Route::get('{id}/attachments/{attachmentId}/download', 'FixedAssetAttachmentController@download')->whereNumber('id');
        Route::delete('{id}/attachments/{attachmentId}', 'FixedAssetAttachmentController@destroy')->whereNumber('id');
        Route::get('{id}/reports/depreciation/{format?}', 'FixedAssetExportController@depreciationSchedule')->whereNumber('id')->where('format', 'pdf|xlsx|xls|csv');
        Route::get('{id}/reports/history/{format?}', 'FixedAssetExportController@history')->whereNumber('id')->where('format', 'pdf|xlsx|xls|csv');
    });
    Route::prefix('purchase-capitalization')->group(function () {
        Route::get('/', 'PurchaseCapitalizationController@index');
        Route::get('{id}', 'PurchaseCapitalizationController@show')->whereNumber('id');
        Route::post('{id}/approve', 'PurchaseCapitalizationController@approve')->whereNumber('id');
        Route::post('{id}/capitalize', 'PurchaseCapitalizationController@capitalize')->whereNumber('id');
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
    Route::prefix('manufacturing-overhead')->group(function () {
        Route::get('rules', 'ManufacturingOverheadController@rulesIndex');
        Route::get('rules/create', 'ManufacturingOverheadController@rulesCreate');
        Route::post('rules', 'ManufacturingOverheadController@rulesStore');
        Route::post('rules/{id}/approve', 'ManufacturingOverheadController@approveRule')->whereNumber('id');
        Route::post('rules/{id}/activate', 'ManufacturingOverheadController@activateRule')->whereNumber('id');
        Route::get('allocation', 'ManufacturingOverheadController@allocationForm');
        Route::post('allocation/preview', 'ManufacturingOverheadController@preview');
        Route::post('runs/{id}/post', 'ManufacturingOverheadController@postRun')->whereNumber('id');
        Route::post('runs/{id}/reverse', 'ManufacturingOverheadController@reverseRun')->whereNumber('id');
        Route::get('history', 'ManufacturingOverheadController@history');
    });
    Route::prefix('cost-center-reports')->group(function () {
        Route::get('profit-and-loss', 'CostCenterReportController@profitAndLoss');
        Route::get('profit-and-loss/{format}', 'CostCenterReportController@profitAndLossExport')->where('format', 'pdf|xlsx|xls|csv');
        Route::get('journals', 'CostCenterReportController@journals');
    });

    // Phase 4 — Advanced financial reporting & analytics
    Route::prefix('phase4')->group(function () {
        Route::get('financial-dashboard', 'FinancialDashboardController@show');
        Route::prefix('statements')->group(function () {
            Route::get('profit-and-loss', 'FinancialStatementController@profitAndLoss');
            Route::get('balance-sheet', 'FinancialStatementController@balanceSheet');
            Route::get('cash-flow', 'FinancialStatementController@cashFlow');
            Route::get('changes-in-equity', 'FinancialStatementController@changesInEquity');
            Route::get('trial-balance', 'FinancialStatementController@trialBalance');
        });
        Route::get('custom-reports', 'CustomReportController@index');
        Route::post('custom-reports', 'CustomReportController@store');
        Route::get('custom-reports/{id}/preview', 'CustomReportController@preview')->whereNumber('id');
        Route::delete('custom-reports/{id}', 'CustomReportController@destroy')->whereNumber('id');
        Route::get('cash-forecast', 'CashForecastController@index');
        Route::post('cash-forecast', 'CashForecastController@store');
        Route::get('kpi-analytics', 'FinancialKpiController@index');
        Route::get('tax', 'TaxManagementController@index');
        Route::post('tax/codes', 'TaxManagementController@storeCode');
        Route::post('tax/periods', 'TaxManagementController@storePeriod');
        Route::get('tax/periods/{id}/summary', 'TaxManagementController@summary')->whereNumber('id');
        Route::get('year-end', 'YearEndClosingController@index');
        Route::post('year-end/years', 'YearEndClosingController@storeYear');
        Route::get('year-end/{id}', 'YearEndClosingController@show')->whereNumber('id');
        Route::post('year-end/{id}/execute', 'YearEndClosingController@execute')->whereNumber('id');
        Route::get('scheduled-reports', 'ScheduledReportController@index');
        Route::post('scheduled-reports', 'ScheduledReportController@store');
    });

    // Phase 5 — AI, OCR, anomalies, insights, automation, review
    Route::prefix('phase5')->group(function () {
        Route::get('assistant', 'AiAssistantController@index');
        Route::post('assistant', 'AiAssistantController@ask');
        Route::get('assistant/conversations/{id}', 'AiAssistantController@show')->whereNumber('id');

        Route::get('ocr', 'OcrDocumentController@index');
        Route::post('ocr', 'OcrDocumentController@store');
        Route::get('ocr/{id}', 'OcrDocumentController@show')->whereNumber('id');
        Route::post('ocr/{id}/process', 'OcrDocumentController@process')->whereNumber('id');
        Route::post('ocr/{id}/draft', 'OcrDocumentController@createDraft')->whereNumber('id');
        Route::get('ocr/{id}/download', 'OcrDocumentController@download')->whereNumber('id');

        Route::get('categorization', 'CategorizationController@index');
        Route::post('categorization', 'CategorizationController@suggest');
        Route::post('categorization/{id}/decide', 'CategorizationController@decide')->whereNumber('id');

        Route::get('alerts', 'AnomalyAlertController@index');
        Route::post('alerts/run', 'AnomalyAlertController@run');
        Route::post('alerts/{id}/status', 'AnomalyAlertController@updateStatus')->whereNumber('id');

        Route::get('insights', 'FinancialInsightsController@index');
        Route::post('insights/generate', 'FinancialInsightsController@generate');

        Route::get('review', 'AiReviewCenterController@index');
        Route::get('review/{id}', 'AiReviewCenterController@show')->whereNumber('id');
        Route::post('review/{id}/decide', 'AiReviewCenterController@decide')->whereNumber('id');

        Route::get('automation', 'AutomationRuleController@index');
        Route::post('automation', 'AutomationRuleController@store');
        Route::get('automation/history', 'AutomationRuleController@history');
        Route::post('automation/{id}/toggle', 'AutomationRuleController@toggle')->whereNumber('id');

        Route::get('settings', 'AiSettingsController@index');
        Route::post('settings', 'AiSettingsController@update');
    });

    // Phase 6 — Xero / QuickBooks parity: audit trail, document drill-down, VAT, AR, AP, controls
    Route::prefix('phase6')->group(function () {
        // 6.1 Audit trail
        Route::get('audit-trail', 'AuditTrailController@index');
        Route::get('audit-trail/document/{type}/{id}', 'AuditTrailController@document')->whereNumber('id');

        // 6.2 Invoice drill-down & journal drill-down
        Route::get('invoices', 'SalesInvoiceController@index');
        Route::get('invoices/{order}', 'SalesInvoiceController@show')->whereNumber('order');
        Route::get('journal/{transactionNumber}', 'JournalDrilldownController@transaction')->where('transactionNumber', '[A-Za-z0-9\-_:.]+');
        Route::get('account-transactions', 'JournalDrilldownController@accountTransactions');

        // 6.3 VAT engine
        Route::get('vat-return', 'VatReturnController@index');
        Route::get('vat-return/{section}', 'VatReturnController@detail')->where('section', 'sales|returns|credit_notes|bills|bill_returns');
        Route::get('tax-invoice-register', 'VatReturnController@register');

        // 6.4 Receivables
        Route::get('credit-notes', 'CreditNoteController@index');
        Route::get('credit-notes/create', 'CreditNoteController@create');
        Route::post('credit-notes', 'CreditNoteController@store');
        Route::get('credit-notes/{id}/pdf', 'CreditNoteController@pdf')->whereNumber('id');
        Route::post('credit-notes/{id}/void', 'CreditNoteController@void')->whereNumber('id');
        Route::get('receivables/overdue', 'ReceivablesController@overdue');
        Route::post('receivables/reminders', 'ReceivablesController@bulkReminders');
        Route::post('invoices/{order}/reminder', 'ReceivablesController@reminder')->whereNumber('order');
        Route::post('invoices/{order}/void', 'ReceivablesController@void')->whereNumber('order');

        // 6.5 Payables
        Route::get('bills', 'BillController@index');
        Route::get('bills/payment-run', 'BillController@paymentRun');
        Route::get('bills/{purchase}', 'BillController@show')->whereNumber('purchase');
        Route::post('bills/{purchase}/approve', 'BillController@approve')->whereNumber('purchase');

        // 6.6 Controls
        Route::get('recode', 'RecodeController@index');
        Route::post('recode', 'RecodeController@store');
        Route::get('search', 'GlobalSearchController@index');
    });

    // Bank accounts — add → import (preview) → review → reconcile → history → settings
    Route::prefix('banking')->group(function () {
        Route::get('/', 'BankAccountController@index');
        Route::get('create', 'BankAccountController@create');
        Route::post('/', 'BankAccountController@store');
        Route::get('{id}', 'BankAccountController@show')->whereNumber('id');
        Route::post('{id}/import/preview', 'BankAccountController@importPreview')->whereNumber('id');
        Route::post('{id}/import/confirm', 'BankAccountController@importConfirm')->whereNumber('id');
        Route::post('{id}/reconcile', 'BankAccountController@reconcileFinish')->whereNumber('id');
        Route::get('{id}/reconciliations/{recId}', 'BankAccountController@reconciliation')->whereNumber('id')->whereNumber('recId');
        Route::post('{id}/reconciliations/{recId}/undo', 'BankAccountController@undoReconciliation')->whereNumber('id')->whereNumber('recId');
        Route::post('{id}/settings', 'BankAccountController@updateSettings')->whereNumber('id');
        Route::post('{id}/archive', 'BankAccountController@archive')->whereNumber('id');
        Route::post('{id}/restore', 'BankAccountController@restore')->whereNumber('id');
        Route::post('{id}/disconnect-feed', 'BankAccountController@disconnectFeed')->whereNumber('id');
    });

    // Phase 7 — reconciliation workspace, payment runs, month-end close, reminders, recurring queue
    Route::prefix('phase7')->group(function () {
        Route::get('reconcile', 'BankReconciliationController@index');
        Route::post('reconcile/import', 'BankReconciliationController@import');
        Route::post('reconcile/imports/{importId}/balance', 'BankReconciliationController@saveBalance')->whereNumber('importId');
        Route::post('reconcile/lines/{lineId}/match', 'BankReconciliationController@match')->whereNumber('lineId');
        Route::post('reconcile/lines/{lineId}/unmatch', 'BankReconciliationController@unmatch')->whereNumber('lineId');
        Route::post('reconcile/lines/{lineId}/exclude', 'BankReconciliationController@exclude')->whereNumber('lineId');
        Route::post('reconcile/lines/{lineId}/post', 'BankReconciliationController@postLine')->whereNumber('lineId');
        Route::post('reconcile/lines/{lineId}/transfer', 'BankReconciliationController@transferLine')->whereNumber('lineId');

        Route::get('payment-runs', 'PaymentRunController@index');
        Route::post('payment-runs', 'PaymentRunController@store');
        Route::get('payment-runs/{id}', 'PaymentRunController@show')->whereNumber('id');
        Route::post('payment-runs/{id}/approve', 'PaymentRunController@approve')->whereNumber('id');
        Route::get('payment-runs/{id}/export', 'PaymentRunController@export')->whereNumber('id');
        Route::post('payment-runs/{id}/items/{itemId}/fail', 'PaymentRunController@fail')->whereNumber('id')->whereNumber('itemId');
        Route::post('payment-runs/{id}/confirm', 'PaymentRunController@confirm')->whereNumber('id');
        Route::post('payment-runs/{id}/cancel', 'PaymentRunController@cancel')->whereNumber('id');

        Route::get('close', 'MonthEndCloseController@index');
        Route::post('close/{key}/sign-off', 'MonthEndCloseController@signOff')->where('key', '\d{4}-\d{2}');
        Route::post('close/{key}/lock', 'MonthEndCloseController@lock')->where('key', '\d{4}-\d{2}');
        Route::post('close/{key}/reopen', 'MonthEndCloseController@reopen')->where('key', '\d{4}-\d{2}');

        Route::get('reminders', 'ReceivablesAutomationController@reminders');
        Route::post('reminders/run', 'ReceivablesAutomationController@runReminders');
        Route::get('recurring-queue', 'ReceivablesAutomationController@recurringQueue');
        Route::post('recurring-queue/{runId}/retry', 'ReceivablesAutomationController@retry')->whereNumber('runId');
    });

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
