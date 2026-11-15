<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ManufacturingController;
use App\Http\Controllers\HourlyProductionController;
use App\Http\Controllers\CostAnalystController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrendingProductsReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductionSectionController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\ProductRecipeController;
use App\Http\Controllers\CommissionAgentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariationController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PriceGroupController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\SalesPersonController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SalesPersonCustomerController;
use App\Http\Controllers\SalesPersonCommissionController;
use App\Http\Controllers\SalesPersonAuthController;
use App\Http\Controllers\SalesPersonOrderController;
use App\Http\Controllers\OrderPaymentManagementController;
use App\Http\Controllers\PurchasePaymentManagementController;
use App\Http\Controllers\HrmController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\HrSuiteController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OtRequestController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VehicleRunController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\OrderPaymentChequeImageController;
use App\Http\Controllers\OrderPaymentChequeSignedImageController;
use App\Http\Controllers\ChequeNotificationSettingsController;
use App\Http\Controllers\OrderPaymentChequeNotificationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\AdminFieldExpenseController;
use App\Http\Controllers\FieldExpenseReceiptController;

Route::get('/', function () { 
    return redirect()->route('login'); 
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/supervisor/login', [AuthController::class, 'showSupervisorLoginForm'])->name('supervisor.login');
Route::post('/supervisor/login', [AuthController::class, 'supervisorLogin'])->name('supervisor.login.post');

Route::middleware(['auth', 'supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('dashboard');
    Route::get('/jobs', [SupervisorController::class, 'jobs'])->name('jobs');
    Route::get('/employees', [SupervisorController::class, 'sectionEmployees'])->name('employees');
    Route::put('/password', [SupervisorController::class, 'updateOwnPassword'])->name('password.update');
    Route::put('/employees/{employee}/password', [SupervisorController::class, 'updateEmployeePassword'])->name('employees.password');
    Route::get('/jobs/{job}/record-usage', [SupervisorController::class, 'jobRecordUsage'])->name('usage.form');
    Route::post('/jobs/{job}/record-usage', [SupervisorController::class, 'storeJobUsage'])->name('usage.store');
    Route::post('/jobs/{job}/line-complete', [SupervisorController::class, 'markLineComplete'])->name('line.complete');
    Route::post('/jobs/{job}/washing-stock', [App\Http\Controllers\WashingStockController::class, 'deposit'])->name('washing-stock.deposit');
});

Route::middleware(['auth', 'erp.permission'])->prefix('erp')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('erp.dashboard');

    Route::get('settings/cheque-notifications', [ChequeNotificationSettingsController::class, 'edit'])->name('erp.settings.cheque-notifications.edit');
    Route::put('settings/cheque-notifications', [ChequeNotificationSettingsController::class, 'update'])->name('erp.settings.cheque-notifications.update');

    // Products Module
    Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import.create');
    Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import.store');
    Route::get('products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
    Route::get('products/{product}/print-barcode', [ProductController::class, 'printBarcode'])->name('products.print-barcode');
    Route::get('products/{product}/history', [ProductController::class, 'stockHistory'])->name('products.history');
    Route::get('products/{product}/stock-adjustment-data', [ProductController::class, 'stockAdjustmentData'])->name('products.stock-adjustment-data');
    Route::post('products/{product}/transfer-channel-stock', [ProductController::class, 'transferChannelStock'])->name('products.transfer-channel-stock');
    Route::post('products/{product}/variations', [ProductVariationController::class, 'store'])->name('products.variations.store');
    Route::post('products/{product}/variations/batch', [ProductVariationController::class, 'storeMany'])->name('products.variations.store_many');
    Route::post('products/{product}/variations/{variation}/prices', [ProductVariationController::class, 'updatePrices'])->name('products.variations.update-prices');
    Route::delete('products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->name('products.variations.destroy');
    Route::post('products/ajax-store', [ProductController::class, 'storeAjax'])->name('products.ajax.store');
    Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('categories', CategoryController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('units', UnitController::class);
    Route::resource('price-groups', PriceGroupController::class);

    // Barcode Printing
    Route::get('/barcode/print', [App\Http\Controllers\BarcodeController::class, 'printForm'])->name('barcode.print');
    Route::match(['get', 'post'], '/barcode/preview', [App\Http\Controllers\BarcodeController::class, 'generatePdf'])->name('barcode.preview');
    Route::get('/barcode/settings', [App\Http\Controllers\BarcodeController::class, 'index'])->name('barcode.index');
    Route::post('/barcode/settings', [App\Http\Controllers\BarcodeController::class, 'storeSetting'])->name('barcode.store_setting');
    Route::put('/barcode/settings/{barcodeSetting}', [App\Http\Controllers\BarcodeController::class, 'updateSetting'])->name('barcode.settings.update');
    Route::delete('/barcode/settings/{barcodeSetting}', [App\Http\Controllers\BarcodeController::class, 'destroySetting'])->name('barcode.settings.destroy');
    Route::get('/barcode/settings/test-print', [App\Http\Controllers\BarcodeController::class, 'testPrint'])->name('barcode.settings.test');

    Route::prefix('essentials')->name('essentials.')->group(function() {
        Route::get('todo', [App\Http\Controllers\Essentials\TodoController::class, 'index'])->name('todo.index');
        Route::post('todo', [App\Http\Controllers\Essentials\TodoController::class, 'store'])->name('todo.store');
        
        Route::resource('memos', App\Http\Controllers\Essentials\MemoController::class)->only(['index', 'store', 'destroy']);
        Route::resource('documents', App\Http\Controllers\Essentials\DocumentController::class)->only(['index', 'store', 'destroy']);
        Route::resource('reminders', App\Http\Controllers\Essentials\ReminderController::class)->only(['index', 'store', 'destroy']);
    });

    // Stock Management
    Route::get('stock-adjustment', [App\Http\Controllers\StockAdjustmentController::class, 'create'])->name('stock-adjustment.create');
    Route::post('stock-adjustment', [App\Http\Controllers\StockAdjustmentController::class, 'store'])->name('stock-adjustment.store');


    // Jobs
    Route::get('jobs/{job}/costs', [JobController::class, 'costs'])->name('jobs.costs');
    Route::post('jobs/{job}/costs', [JobController::class, 'storeCost'])->name('jobs.costs.store');
    Route::delete('jobs/{job}/costs/{cost}', [JobController::class, 'destroyCost'])->name('jobs.costs.destroy');
    Route::post('jobs/{job}/send-to-cutting', [JobController::class, 'sendToCutting'])->name('jobs.send_to_cutting');
    Route::post('jobs/{job}/end', [JobController::class, 'endJob'])->name('jobs.end');
    Route::post('jobs/{job}/reopen', [JobController::class, 'reopenJob'])->name('jobs.reopen');
    Route::get('jobs/{job}/assign-line', [JobController::class, 'assignLineForm'])->name('jobs.assign_line');
    Route::post('jobs/{job}/assign-line', [JobController::class, 'storeLineAssignment'])->name('jobs.assign_line.store');
    Route::resource('jobs', JobController::class)->only(['index', 'create', 'store', 'show']);
    
    // Departments / Sections
    Route::resource('sections', ProductionSectionController::class)->only(['index', 'show', 'store']);
    Route::post('sections/seed-defaults', [ProductionSectionController::class, 'seedDefaults'])->name('sections.seed-defaults');
    Route::post('sections/{section}/assign', [ProductionSectionController::class, 'storeAssignments'])->name('sections.assign.store');
    Route::resource('lines', ProductionLineController::class)->except(['show']);
    Route::get('lines/{line}/assign', [ProductionLineController::class, 'assignEmployeesForm'])->name('lines.assign');
    Route::post('lines/{line}/assign', [ProductionLineController::class, 'assignEmployeesStore'])->name('lines.assign.store');
    
    // Employees
    Route::get('employees/import', [App\Http\Controllers\EmployeeImportController::class, 'create'])->name('employees.import.create');
    Route::post('employees/import', [App\Http\Controllers\EmployeeImportController::class, 'store'])->name('employees.import.store');
    Route::get('employees/hik-connect-import', [App\Http\Controllers\HikConnectEmployeeImportController::class, 'create'])->name('employees.hik-connect-import.create');
    Route::post('employees/hik-connect-import/preview', [App\Http\Controllers\HikConnectEmployeeImportController::class, 'preview'])->name('employees.hik-connect-import.preview');
    Route::post('employees/hik-connect-import', [App\Http\Controllers\HikConnectEmployeeImportController::class, 'store'])->name('employees.hik-connect-import.store');
    Route::post('employees/bulk-delete', [EmployeeController::class, 'destroyBulk'])->name('employees.bulk-delete');
    Route::post('employees/{employee}/bonuses', [EmployeeController::class, 'storeBonus'])->name('employees.bonuses.store');
    Route::post('employees/{employee}/photo', [EmployeeController::class, 'updatePhoto'])->name('employees.photo.update');
    Route::post('employees/{employee}/shift', [EmployeeController::class, 'assignShift'])->name('employees.shift.assign');
    Route::post('employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::post('employees/{employee}/documents', [EmployeeController::class, 'storeDocument'])->name('employees.documents.store');
    Route::delete('employees/{employee}/documents/{document}', [EmployeeController::class, 'destroyDocument'])->name('employees.documents.destroy');
    Route::post('employees/{employee}/changes', [EmployeeController::class, 'storeChange'])->name('employees.changes.store');
    Route::resource('employees', EmployeeController::class);

    Route::get('supervisors', [App\Http\Controllers\SupervisorManagementController::class, 'index'])->name('supervisors.index');
    Route::put('supervisors/{employee}/password', [App\Http\Controllers\SupervisorManagementController::class, 'updatePassword'])->name('supervisors.password.update');
    
    // Inventory
    Route::get('inventory/{material}/history', [InventoryController::class, 'history'])->name('inventory.history');
    Route::resource('inventory', InventoryController::class)->except(['show']);
    Route::get('inventory/issue', [InventoryController::class, 'issueForm'])->name('inventory.issue');
    Route::post('inventory/issue', [InventoryController::class, 'issueMaterial'])->name('inventory.issue.store');
    Route::post('inventory/quick-store', [InventoryController::class, 'quickStore'])->name('inventory.quick-store');

    // Purchases & Suppliers
    Route::get('suppliers/{supplier}/export-purchases', [App\Http\Controllers\SupplierController::class, 'exportPurchases'])
        ->name('suppliers.export-purchases');
    Route::resource('suppliers', App\Http\Controllers\SupplierController::class);
    Route::resource('purchases', App\Http\Controllers\PurchaseController::class)->except(['destroy']);
    Route::get('purchases/{purchase}/report', [App\Http\Controllers\PurchaseController::class, 'report'])->name('purchases.report');
    Route::post('purchases/{purchase}/confirm', [App\Http\Controllers\PurchaseController::class, 'confirm'])->name('purchases.confirm');
    Route::post('purchases/{purchase}/cancel', [App\Http\Controllers\PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('purchase-payments', [App\Http\Controllers\PurchasePaymentController::class, 'store'])->name('purchase-payments.store');
    Route::post('purchase-payments/refund', [App\Http\Controllers\PurchasePaymentController::class, 'storeRefund'])->name('purchase-payments.refund');
    Route::post('purchases/{purchase}/returns', [App\Http\Controllers\PurchaseReturnController::class, 'store'])->name('purchases.returns.store');
    Route::get('purchase-payment-management/returns', [App\Http\Controllers\PurchaseReturnController::class, 'index'])->name('purchase-payment-management.returns');
    Route::get('purchase-payment-management', [PurchasePaymentManagementController::class, 'index'])->name('purchase-payment-management.index');
    Route::get('purchase-payment-management/payments', [PurchasePaymentManagementController::class, 'payments'])->name('purchase-payment-management.payments');
    Route::get('purchase-payment-management/cheque-payments', [PurchasePaymentManagementController::class, 'cheques'])->name('purchase-payment-management.cheques');

    // Recipes
    Route::get('recipes/by-product/{product}', [ProductRecipeController::class, 'getRecipeByProduct'])->name('recipes.by_product');
    Route::resource('product-recipes', ProductRecipeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    
    // Hourly Production
    Route::get('production/hourly', [HourlyProductionController::class, 'create'])->name('hourly-production.create');
    Route::post('production/hourly/daily-targets', [HourlyProductionController::class, 'storeDailyTargets'])->name('hourly-production.daily-targets');
    Route::post('production/hourly', [HourlyProductionController::class, 'store'])->name('hourly-production.store');
    Route::get('production/hourly/print', [HourlyProductionController::class, 'printReport'])->name('hourly-production.print');
    
    // Manufacturing
    Route::get('manufacturing', [ManufacturingController::class, 'overview'])->name('manufacturing.overview');
    Route::get('manufacturing/cutting', [ManufacturingController::class, 'cuttingIndex'])->name('manufacturing.cutting');
    Route::post('manufacturing/cutting/save', [ManufacturingController::class, 'storeCutting'])->name('manufacturing.cutting.save');
    
    Route::get('manufacturing/washing-stock', [App\Http\Controllers\WashingStockController::class, 'index'])->name('manufacturing.washing-stock.index');
    Route::get('manufacturing/washing-stock/{job}', [App\Http\Controllers\WashingStockController::class, 'show'])->name('manufacturing.washing-stock.show');
    Route::post('manufacturing/washing-stock/{job}', [App\Http\Controllers\WashingStockController::class, 'deposit'])->name('manufacturing.washing-stock.deposit');

    Route::get('manufacturing/washing/report', [App\Http\Controllers\WashingReportController::class, 'index'])->name('manufacturing.washing.report');
    Route::get('manufacturing/washing/report/export', [App\Http\Controllers\WashingReportController::class, 'export'])->name('manufacturing.washing.report.export');
    Route::get('manufacturing/washing', [ManufacturingController::class, 'washingIndex'])->name('manufacturing.washing');
    Route::post('manufacturing/washing/save', [ManufacturingController::class, 'storeWashing'])->name('manufacturing.washing.save');
    Route::get('manufacturing/washing/{id}/print', [ManufacturingController::class, 'printWashingGatePass'])->name('manufacturing.washing.print');
    Route::post('manufacturing/washing/{id}/receive', [ManufacturingController::class, 'receiveWashing'])->name('manufacturing.washing.receive');
    Route::post('manufacturing/washing/receive-by-scan', [ManufacturingController::class, 'receiveWashingByScan'])->name('manufacturing.washing.receive-scan');
    Route::post('manufacturing/washing/{id}/receive-quantity', [ManufacturingController::class, 'receiveWashingQuantity'])->name('manufacturing.washing.receive-quantity');
    Route::post('manufacturing/washing/{id}/complete', [ManufacturingController::class, 'completeWashing'])->name('manufacturing.washing.complete');

    Route::get('manufacturing/qc', [ManufacturingController::class, 'qcIndex'])->name('manufacturing.qc');
    Route::get('manufacturing/qc/{id}', [ManufacturingController::class, 'qcJobDetail'])->name('manufacturing.qc.detail');
    Route::post('manufacturing/qc/save', [ManufacturingController::class, 'storeQc'])->name('manufacturing.qc.save');
    Route::post('manufacturing/qc/accessory', [ManufacturingController::class, 'addQcAccessory'])->name('manufacturing.qc.accessory');
    Route::post('manufacturing/qc/complete', [ManufacturingController::class, 'completePacking'])->name('manufacturing.qc.complete');
    Route::post('manufacturing/qc/variation-qty', [ManufacturingController::class, 'savePackingVariationQty'])->name('manufacturing.qc.variation-qty');
    
    Route::get('manufacturing/packing/print/{id}', [ManufacturingController::class, 'printBarcode'])->name('manufacturing.packing.print');
    
    Route::get('manufacturing/final', [ManufacturingController::class, 'finalCheckingIndex'])->name('manufacturing.final.index');
    Route::get('manufacturing/final/{id}', [ManufacturingController::class, 'finalCheckingDetail'])->name('manufacturing.final.detail');
    Route::post('manufacturing/final/batch/{id}', [ManufacturingController::class, 'finalizeBatch'])->name('manufacturing.packing.finalize');
    
    // Agents
    Route::resource('agents', CommissionAgentController::class)->only(['index', 'create', 'store']);
    
    // HRM & Attendance
    Route::get('hrm', [HrmController::class, 'index'])->name('hrm.index');
    Route::get('attendance/import', [App\Http\Controllers\AttendanceImportController::class, 'showImportForm'])->name('attendance.import');
    Route::post('attendance/import', [App\Http\Controllers\AttendanceImportController::class, 'import'])->name('attendance.import.store');
    Route::get('attendance/hik-connect-import', [App\Http\Controllers\HikConnectAttendanceImportController::class, 'create'])->name('attendance.hik-connect-import.create');
    Route::post('attendance/hik-connect-import/preview', [App\Http\Controllers\HikConnectAttendanceImportController::class, 'preview'])->name('attendance.hik-connect-import.preview');
    Route::post('attendance/hik-connect-import', [App\Http\Controllers\HikConnectAttendanceImportController::class, 'store'])->name('attendance.hik-connect-import.store');
    Route::get('attendance/manual', [AttendanceController::class, 'manualMark'])->name('attendance.manual');
    Route::post('attendance/manual', [AttendanceController::class, 'storeManual'])->name('attendance.manual.store');
    Route::resource('attendance', AttendanceController::class)->only(['index']);
    Route::resource('departments', DepartmentController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('designations', DesignationController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('shifts', ShiftController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('leave-types', LeaveTypeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::patch('leave-requests/{leave_request}/supervisor', [LeaveRequestController::class, 'supervisorApprove'])->name('leave-requests.supervisor');
    Route::patch('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::patch('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::patch('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
    Route::get('leave-requests/balances', [LeaveRequestController::class, 'balances'])->name('leave-requests.balances');
    Route::get('leave-requests/calendar', [LeaveRequestController::class, 'calendar'])->name('leave-requests.calendar');

    Route::get('ot-requests', [OtRequestController::class, 'index'])->name('ot-requests.index');
    Route::get('ot-requests/create', [OtRequestController::class, 'create'])->name('ot-requests.create');
    Route::post('ot-requests', [OtRequestController::class, 'store'])->name('ot-requests.store');
    Route::patch('ot-requests/{ot_request}/supervisor', [OtRequestController::class, 'supervisorApprove'])->name('ot-requests.supervisor');
    Route::patch('ot-requests/{ot_request}/approve', [OtRequestController::class, 'approve'])->name('ot-requests.approve');
    Route::patch('ot-requests/{ot_request}/reject', [OtRequestController::class, 'reject'])->name('ot-requests.reject');

    Route::get('hr/incentives', [HrSuiteController::class, 'incentives'])->name('hr.incentives.index');
    Route::post('hr/incentives', [HrSuiteController::class, 'storeIncentive'])->name('hr.incentives.store');
    Route::patch('hr/incentives/{incentiveClaim}/approve', [HrSuiteController::class, 'approveIncentive'])->name('hr.incentives.approve');
    Route::get('hr/skills', [HrSuiteController::class, 'skills'])->name('hr.skills.index');
    Route::post('hr/skills', [HrSuiteController::class, 'storeSkill'])->name('hr.skills.store');
    Route::get('hr/training', [HrSuiteController::class, 'training'])->name('hr.training.index');
    Route::post('hr/training', [HrSuiteController::class, 'storeTraining'])->name('hr.training.store');
    Route::get('hr/manpower', [HrSuiteController::class, 'manpower'])->name('hr.manpower.index');
    Route::post('hr/manpower', [HrSuiteController::class, 'storeManpower'])->name('hr.manpower.store');
    Route::get('hr/recruitment', [HrSuiteController::class, 'recruitment'])->name('hr.recruitment.index');
    Route::post('hr/recruitment', [HrSuiteController::class, 'storeRecruitment'])->name('hr.recruitment.store');
    Route::get('hr/performance', [HrSuiteController::class, 'performance'])->name('hr.performance.index');
    Route::post('hr/performance', [HrSuiteController::class, 'storePerformance'])->name('hr.performance.store');
    Route::get('hr/welfare', [HrSuiteController::class, 'welfare'])->name('hr.welfare.index');
    Route::post('hr/welfare', [HrSuiteController::class, 'storeWelfare'])->name('hr.welfare.store');
    Route::get('hr/safety', [HrSuiteController::class, 'safety'])->name('hr.safety.index');
    Route::post('hr/safety', [HrSuiteController::class, 'storeSafety'])->name('hr.safety.store');
    Route::get('hr/grievances', [HrSuiteController::class, 'grievances'])->name('hr.grievances.index');
    Route::post('hr/grievances', [HrSuiteController::class, 'storeGrievance'])->name('hr.grievances.store');
    Route::get('hr/letters', [HrSuiteController::class, 'letters'])->name('hr.letters.index');
    Route::post('hr/letters', [HrSuiteController::class, 'storeLetter'])->name('hr.letters.store');
    Route::get('hr/resignations', [HrSuiteController::class, 'resignations'])->name('hr.resignations.index');
    Route::post('hr/resignations', [HrSuiteController::class, 'storeResignation'])->name('hr.resignations.store');
    Route::patch('hr/resignations/{id}/confirm', [HrSuiteController::class, 'confirmResignation'])->name('hr.resignations.confirm');
    Route::get('hr/self', [HrSuiteController::class, 'selfService'])->name('hr.self');
    Route::post('hr/self/request', [HrSuiteController::class, 'storeSelfRequest'])->name('hr.self.request');
    Route::patch('hr/reviews/{id}/acknowledge', [HrSuiteController::class, 'acknowledgeReview'])->name('hr.reviews.acknowledge');
    Route::patch('hr/letters/{id}/acknowledge', [HrSuiteController::class, 'acknowledgeLetter'])->name('hr.letters.acknowledge');
    Route::get('hr/reports', [HrSuiteController::class, 'reports'])->name('hr.reports');
    Route::get('hr/reports/{type}', [HrSuiteController::class, 'exportReport'])->name('hr.reports.export');
    Route::get('hr/settings', [HrSuiteController::class, 'settings'])->name('hr.settings');

    // Roster (shift assignment by date)
    Route::get('roster', [RosterController::class, 'index'])->name('roster.index');
    Route::post('roster', [RosterController::class, 'store'])->name('roster.store');
    Route::delete('roster/{employee_shift}', [RosterController::class, 'destroy'])->name('roster.destroy');

    // Announcements & Notices (Ultimate POS style)
    Route::resource('announcements', AnnouncementController::class)->except(['show']);

    // Salary / Payroll (HRM)
    Route::resource('salary-components', SalaryComponentController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('employee-salaries', [EmployeeSalaryController::class, 'index'])->name('employee-salaries.index');
    Route::get('employee-salaries/create', [EmployeeSalaryController::class, 'create'])->name('employee-salaries.create');
    Route::post('employee-salaries', [EmployeeSalaryController::class, 'store'])->name('employee-salaries.store');
    Route::get('payroll-runs', [PayrollRunController::class, 'index'])->name('payroll-runs.index');
    Route::get('payroll-runs/create', [PayrollRunController::class, 'create'])->name('payroll-runs.create');
    Route::post('payroll-runs', [PayrollRunController::class, 'store'])->name('payroll-runs.store');
    Route::get('payroll-runs/{payroll_run}', [PayrollRunController::class, 'show'])->name('payroll-runs.show');
    Route::post('payroll-runs/{payroll_run}/add-employee', [PayrollRunController::class, 'addEmployee'])->name('payroll-runs.add-employee');
    Route::patch('payroll-runs/{payroll_run}/review', [PayrollRunController::class, 'review'])->name('payroll-runs.review');
    Route::patch('payroll-runs/{payroll_run}/approve', [PayrollRunController::class, 'approveRun'])->name('payroll-runs.approve-run');
    Route::get('payroll-runs/{payroll_run}/bank', [PayrollRunController::class, 'exportBank'])->name('payroll-runs.bank-export');
    Route::patch('payroll-runs/{payroll_run}/finalize', [PayrollRunController::class, 'finalize'])->name('payroll-runs.finalize');
    Route::get('payroll-runs/{payroll_run}/items/{payroll_run_item}/edit', [PayrollRunController::class, 'editItem'])->name('payroll-runs.items.edit');
    Route::put('payroll-runs/{payroll_run}/items/{payroll_run_item}', [PayrollRunController::class, 'updateItem'])->name('payroll-runs.items.update');
    Route::post('payroll-runs/items/{payroll_run_item}/mark-paid', [PayrollRunController::class, 'markAsPaid'])->name('payroll-runs.items.mark-paid');
    Route::get('payroll-runs/{payroll_run}/payslip/{payroll_run_item}', [PayrollRunController::class, 'payslip'])->name('payroll-runs.payslip');

    // Expenses — bulk wipe disabled (Phase 1): protected journals / financial history.
    // Route name kept so bookmarks and permission probes do not 404.
    Route::match(['get', 'post'], 'expenses/clear-all', function () {
        abort(410, 'Bulk expense deletion is disabled to protect accounting journals. Delete expenses individually.');
    })->name('expenses.clear-all');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::get('field-expenses/sales-person', [AdminFieldExpenseController::class, 'salesPerson'])->name('field-expenses.sales-person.index');
    Route::post('field-expenses/sales-person/delete-today', [AdminFieldExpenseController::class, 'destroyToday'])->name('field-expenses.sales-person.delete-today');
    Route::delete('field-expenses/sales-person/{expense}', [AdminFieldExpenseController::class, 'destroy'])->name('field-expenses.sales-person.destroy');
    Route::get('field-expenses/drivers', [AdminFieldExpenseController::class, 'drivers'])->name('field-expenses.drivers.index');

    // Vehicle Management
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::post('vehicles/{vehicle}/assignments', [VehicleController::class, 'storeAssignment'])->name('vehicles.assignments.store');
    Route::delete('vehicles/{vehicle}/assignments/{assignment}', [VehicleController::class, 'destroyAssignment'])->name('vehicles.assignments.destroy');
    Route::resource('drivers', DriverController::class)->except(['show']);
    Route::get('vehicle-runs', [VehicleRunController::class, 'index'])->name('vehicle-runs.index');

    // Cost Analyst (admin — manufacturing completed jobs, pricing AI)
    Route::get('cost-analyst', [CostAnalystController::class, 'index'])->name('cost-analyst.index');
    Route::get('cost-analyst/jobs/{job}', [CostAnalystController::class, 'show'])->name('cost-analyst.show');

    // Reports (Ultimate POS style + Export PDF/Excel)
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/trending-products/products/{product}', [TrendingProductsReportController::class, 'show'])->name('reports.trending-products.show');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');

    // Roles
    Route::resource('roles', RoleController::class)->except(['show']);

    // User Management
    Route::get('users',              [UserManagementController::class, 'index'])  ->name('erp.users.index');
    Route::get('users/create',       [UserManagementController::class, 'create']) ->name('erp.users.create');
    Route::post('users',             [UserManagementController::class, 'store'])  ->name('erp.users.store');
    Route::get('users/{user}/edit',  [UserManagementController::class, 'edit'])   ->name('erp.users.edit');
    Route::put('users/{user}',       [UserManagementController::class, 'update']) ->name('erp.users.update');
    Route::delete('users/{user}',    [UserManagementController::class, 'destroy'])->name('erp.users.destroy');

    // Customers (Admin View)
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('erp.customers.index');
    Route::get('customers/create', [AdminCustomerController::class, 'create'])->name('erp.customers.create');
    Route::post('customers', [AdminCustomerController::class, 'store'])->name('erp.customers.store');
    Route::get('customers/{customer}/edit', [AdminCustomerController::class, 'edit'])->name('erp.customers.edit');
    Route::put('customers/{customer}', [AdminCustomerController::class, 'update'])->name('erp.customers.update');
    Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('erp.customers.show');
    Route::post('customers/{customer}/previous-return', [AdminCustomerController::class, 'storePreviousReturn'])->name('erp.customers.previous-return.store');
    Route::delete('customers/{customer}', [AdminCustomerController::class, 'destroy'])->name('erp.customers.destroy');

    // Sales Persons
    Route::post('sales-persons/{id}/resend', [SalesPersonController::class, 'resendCredentials'])->name('sales-persons.resend');
    Route::post('sales-persons/{id}/reassign', [SalesPersonController::class, 'reassign'])->name('sales-persons.reassign');
    Route::post('sales-persons/{id}/assign-route', [SalesPersonController::class, 'assignRoute'])->name('sales-persons.assign-route');
    Route::resource('sales-persons', SalesPersonController::class)->except(['show']);
    Route::get('sales-routes/live', [App\Http\Controllers\AdminSalesRouteRunController::class, 'live'])->name('sales-routes.live');
    Route::get('sales-routes/runs', [App\Http\Controllers\AdminSalesRouteRunController::class, 'index'])->name('sales-routes.runs');
    Route::get('sales-routes/runs/{run}', [App\Http\Controllers\AdminSalesRouteRunController::class, 'show'])->name('sales-routes.runs.show');
    Route::post('sales-routes/runs/{run}/petty-cash', [App\Http\Controllers\AdminSalesRouteRunController::class, 'storePettyCash'])->name('sales-routes.runs.petty-cash');
    Route::resource('sales-routes', App\Http\Controllers\SalesRouteController::class)->except(['show']);

    // Sales Commissions
    Route::get('commissions', [App\Http\Controllers\SalesCommissionController::class, 'index'])->name('commissions.index');
    Route::get('commissions/history', [App\Http\Controllers\SalesCommissionController::class, 'history'])->name('commissions.history');
    Route::get('commissions/export', [App\Http\Controllers\SalesCommissionController::class, 'exportPdf'])->name('commissions.export');
    Route::post('commissions/update', [App\Http\Controllers\SalesCommissionController::class, 'update'])->name('commissions.update');

    // POS (Retail sales – UltimatePOS style)
    Route::get('pos', [App\Http\Controllers\PosController::class, 'index'])->name('pos.index');
    Route::get('pos/receipt/{order}', [App\Http\Controllers\PosController::class, 'receipt'])->name('pos.receipt');
    Route::get('pos/receipt/{order}/pdf', [App\Http\Controllers\PosController::class, 'receiptPdf'])->name('pos.receipt.pdf');
    Route::get('pos/products', [App\Http\Controllers\PosController::class, 'getProducts'])->name('pos.products');
    Route::get('pos/products/search', [App\Http\Controllers\PosController::class, 'productSearch'])->name('pos.products.search');
    Route::get('pos/products/barcode/{code}', [App\Http\Controllers\PosController::class, 'barcodeLookup'])->name('pos.products.barcode');
    Route::post('pos/customers', [App\Http\Controllers\PosController::class, 'storeCustomer'])->name('pos.customers.store');
    Route::post('pos', [App\Http\Controllers\PosController::class, 'store'])->name('pos.store');

    Route::post('pos/cart/add', [App\Http\Controllers\PosCartController::class, 'add'])->name('pos.cart.add');
    Route::post('pos/cart/update', [App\Http\Controllers\PosCartController::class, 'update'])->name('pos.cart.update');
    Route::post('pos/cart/remove', [App\Http\Controllers\PosCartController::class, 'remove'])->name('pos.cart.remove');
    Route::post('pos/cart/clear', [App\Http\Controllers\PosCartController::class, 'clear'])->name('pos.cart.clear');
    Route::post('pos/cart/meta', [App\Http\Controllers\PosCartController::class, 'updateMeta'])->name('pos.cart.meta');
    Route::get('pos/cart/totals', [App\Http\Controllers\PosCartController::class, 'totals'])->name('pos.cart.totals');

    Route::post('pos/sale/draft', [App\Http\Controllers\PosSaleController::class, 'saveDraft'])->name('pos.sale.draft');
    Route::post('pos/sale/suspend', [App\Http\Controllers\PosSaleController::class, 'saveSuspend'])->name('pos.sale.suspend');
    Route::post('pos/sale/quotation', [App\Http\Controllers\PosSaleController::class, 'saveQuotation'])->name('pos.sale.quotation');
    Route::post('pos/sale/finalize', [App\Http\Controllers\PosSaleController::class, 'finalize'])->name('pos.sale.finalize');
    Route::get('pos/sales/recent', [App\Http\Controllers\PosSaleController::class, 'recent'])->name('pos.sales.recent');
    Route::post('pos/sale/load/{order}', [App\Http\Controllers\PosSaleController::class, 'loadSale'])->name('pos.sale.load');

    Route::get('pos/register/status', [App\Http\Controllers\PosRegisterController::class, 'status'])->name('pos.register.status');
    Route::post('pos/register/open', [App\Http\Controllers\PosRegisterController::class, 'open'])->name('pos.register.open');
    Route::post('pos/register/close', [App\Http\Controllers\PosRegisterController::class, 'close'])->name('pos.register.close');

    Route::post('pos/expenses', [App\Http\Controllers\PosExpenseController::class, 'store'])->name('pos.expenses.store');
    Route::get('pos/repairs', [App\Http\Controllers\PosRepairController::class, 'index'])->name('pos.repairs.index');
    Route::post('pos/repairs', [App\Http\Controllers\PosRepairController::class, 'store'])->name('pos.repairs.store');

    // Orders (Admin)
    Route::get('payment-management', [OrderPaymentManagementController::class, 'index'])->name('payment-management.index');
    Route::get('payment-management/cheque-payments', [OrderPaymentManagementController::class, 'chequePayments'])->name('payment-management.cheques');

    Route::get('sale-returns', [App\Http\Controllers\SaleReturnController::class, 'index'])->name('sale-returns.index');
    Route::get('sale-returns/create', [App\Http\Controllers\SaleReturnController::class, 'create'])->name('sale-returns.create');
    Route::get('sale-returns/lookup-item', [App\Http\Controllers\SaleReturnController::class, 'lookupItem'])->name('sale-returns.lookup-item');
    Route::post('sale-returns', [App\Http\Controllers\SaleReturnController::class, 'store'])->name('sale-returns.store');
    Route::get('sale-returns/{saleReturn}', [App\Http\Controllers\SaleReturnController::class, 'show'])->name('sale-returns.show');
    Route::post('orders/{order}/returns', [App\Http\Controllers\SaleReturnController::class, 'store'])->name('orders.returns.store');

    Route::get('retail-sale-returns', [App\Http\Controllers\RetailSaleReturnController::class, 'index'])->name('retail-sale-returns.index');
    Route::get('retail-sale-returns/create', [App\Http\Controllers\RetailSaleReturnController::class, 'create'])->name('retail-sale-returns.create');
    Route::post('retail-sale-returns', [App\Http\Controllers\RetailSaleReturnController::class, 'store'])->name('retail-sale-returns.store');
    Route::get('retail-sale-returns/{saleReturn}', [App\Http\Controllers\RetailSaleReturnController::class, 'show'])->name('retail-sale-returns.show');

    Route::get('retail-quotations', [App\Http\Controllers\RetailQuotationController::class, 'index'])->name('retail-quotations.index');
    Route::get('retail-quotations/{order}', [App\Http\Controllers\RetailQuotationController::class, 'show'])->name('retail-quotations.show');
    Route::post('retail-quotations/{order}/open-pos', [App\Http\Controllers\RetailQuotationController::class, 'openInPos'])->name('retail-quotations.open-pos');

    Route::get('orders/history', [App\Http\Controllers\AdminOrderController::class, 'history'])->name('orders.history');
    Route::get('orders/history/pdf', [App\Http\Controllers\AdminOrderController::class, 'downloadHistoryPdf'])->name('orders.history.pdf');
    Route::get('orders/{order}/invoice', [App\Http\Controllers\AdminOrderController::class, 'downloadInvoice'])->name('orders.invoice');
    Route::get('orders/{order}/order-form', [App\Http\Controllers\AdminOrderController::class, 'downloadOrderForm'])->name('orders.order-form');
    Route::post('orders/{order}/payment', [App\Http\Controllers\AdminOrderController::class, 'addPayment'])->name('orders.payment.store');
    Route::post('orders/{order}/status', [App\Http\Controllers\AdminOrderController::class, 'updateStatus'])->name('orders.update_status');
    Route::post('orders/{order}/order-check', [App\Http\Controllers\AdminOrderController::class, 'completeOrderCheck'])->name('orders.complete_order_check');
    Route::post('orders/{order}/issue-invoice', [App\Http\Controllers\AdminOrderController::class, 'issueInvoice'])->name('orders.issue_invoice');
    Route::put('orders/{order}/invoice', [App\Http\Controllers\AdminOrderController::class, 'updateInvoice'])->name('orders.update_invoice');
    Route::resource('orders', App\Http\Controllers\AdminOrderController::class);

    // Manual cheque reminder to customer
    Route::post('order-payments/{payment}/remind', [OrderPaymentChequeNotificationController::class, 'send'])->name('order-payments.remind');
    Route::post('order-payments/{payment}/cheque/deposit', [App\Http\Controllers\OrderChequePaymentController::class, 'deposit'])->name('order-payments.cheque.deposit');
    Route::post('order-payments/{payment}/cheque/clear', [App\Http\Controllers\OrderChequePaymentController::class, 'clear'])->name('order-payments.cheque.clear');
    Route::post('order-payments/{payment}/cheque/bounce', [App\Http\Controllers\OrderChequePaymentController::class, 'bounce'])->name('order-payments.cheque.bounce');
    
    // Helper for variations (reusing logic, accessible to admin)
    Route::get('products-variations/{product}', [SalesPersonOrderController::class, 'getProductVariations'])->name('products.get_variations');
});

// Sales Person Auth
Route::get('/saleperson/login', [SalesPersonAuthController::class, 'showLoginForm'])->name('sales-person.login');
Route::post('/saleperson/login', [SalesPersonAuthController::class, 'login'])->name('sales-person.login.post');
Route::post('/saleperson/logout', [SalesPersonAuthController::class, 'logout'])->middleware('auth')->name('sales-person.logout');

Route::middleware(['auth', 'sales_person'])->prefix('sales-person')->name('sales-person.')->group(function () {
    Route::get('/dashboard', [SalesDashboardController::class, 'index'])->name('dashboard');
    Route::get('/route', [App\Http\Controllers\SalesPersonRouteController::class, 'show'])->name('route.show');
    Route::post('/route/start', [App\Http\Controllers\SalesPersonRouteController::class, 'start'])->name('route.start');
    Route::post('/route/complete', [App\Http\Controllers\SalesPersonRouteController::class, 'complete'])->name('route.complete');
    Route::get('/expenses', [App\Http\Controllers\SalesPersonExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [App\Http\Controllers\SalesPersonExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [App\Http\Controllers\SalesPersonExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/sales', [SalesDashboardController::class, 'sales'])->name('sales.index');
    Route::get('/credit-report', [App\Http\Controllers\SalesPersonCreditReportController::class, 'index'])->name('credit-report.index');
    Route::get('/commission', [SalesPersonCommissionController::class, 'index'])->name('commission.index');
    Route::get('/products/search', [SalesPersonOrderController::class, 'searchProducts'])->name('products.search');
    Route::get('/products/{product}/variations', [SalesPersonOrderController::class, 'getProductVariations'])->name('products.variations');
    Route::post('/customers', [SalesPersonCustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/previous-return', [SalesPersonCustomerController::class, 'storePreviousReturn'])->name('customers.previous-return.store');
    Route::get('/customers/search', [SalesPersonCustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}/edit', [SalesPersonCustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [SalesPersonCustomerController::class, 'update'])->name('customers.update');
    Route::resource('customers', SalesPersonCustomerController::class)->only(['index', 'create', 'show']);
    Route::post('/orders/{order}/payment', [SalesPersonOrderController::class, 'addPayment'])->name('orders.payment.store');
    Route::get('/orders/{order}/invoice', [SalesPersonOrderController::class, 'downloadInvoice'])->name('orders.invoice');
    Route::get('/orders/{order}/order-form', [SalesPersonOrderController::class, 'downloadOrderForm'])->name('orders.order-form');
    Route::post('/orders/{order}/returns', [App\Http\Controllers\SaleReturnController::class, 'store'])->name('orders.returns.store');
    Route::get('/multi-cheques/orders', [App\Http\Controllers\SalesPersonChequeBatchController::class, 'searchOrders'])->name('multi-cheques.orders');
    Route::get('/multi-cheques', [App\Http\Controllers\SalesPersonChequeBatchController::class, 'index'])->name('multi-cheques.index');
    Route::post('/multi-cheques', [App\Http\Controllers\SalesPersonChequeBatchController::class, 'store'])->name('multi-cheques.store');
    Route::get('/sale-returns', [App\Http\Controllers\SaleReturnController::class, 'index'])->name('sale-returns.index');
    Route::get('/sale-returns/create', [App\Http\Controllers\SaleReturnController::class, 'create'])->name('sale-returns.create');
    Route::get('/sale-returns/lookup-item', [App\Http\Controllers\SaleReturnController::class, 'lookupItem'])->name('sale-returns.lookup-item');
    Route::post('/sale-returns', [App\Http\Controllers\SaleReturnController::class, 'store'])->name('sale-returns.store');
    Route::get('/sale-returns/{saleReturn}', [App\Http\Controllers\SaleReturnController::class, 'show'])->name('sale-returns.show');
    Route::post('/orders/draft', [SalesPersonOrderController::class, 'saveDraft'])->name('orders.draft');
    Route::get('/orders/{order}/edit', [SalesPersonOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [SalesPersonOrderController::class, 'update'])->name('orders.update');
    Route::resource('orders', SalesPersonOrderController::class)->only(['index', 'create', 'store', 'show']);
});

Route::get('/order-payments/{payment}/cheque-image-signed', OrderPaymentChequeSignedImageController::class)
    ->middleware('signed')
    ->name('order-payments.cheque-image-signed');

Route::middleware(['auth'])->get('/order-payments/{payment}/cheque-image', OrderPaymentChequeImageController::class)
    ->name('order-payments.cheque-image');

Route::middleware(['auth'])->group(function () {
    Route::get('/field-expenses/sales-person/{expense}/receipt', [FieldExpenseReceiptController::class, 'salesPerson'])
        ->name('field-expenses.sales-person.receipt');
    Route::get('/field-expenses/driver/{item}/receipt', [FieldExpenseReceiptController::class, 'driver'])
        ->name('field-expenses.driver.receipt');
});

Route::middleware(['auth', 'driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::post('/start-day', [DriverDashboardController::class, 'startDay'])->name('start-day');
    Route::post('/add-item', [DriverDashboardController::class, 'addItem'])->name('add-item');
    Route::post('/end-day', [DriverDashboardController::class, 'endDay'])->name('end-day');
    Route::delete('/items/{item}', [DriverDashboardController::class, 'deleteItem'])->name('delete-item');
    Route::get('/jobs', [DriverDashboardController::class, 'myJobs'])->name('jobs.index');
    Route::get('/jobs/{run}', [DriverDashboardController::class, 'showJob'])->name('jobs.show');
    Route::put('/jobs/{run}', [DriverDashboardController::class, 'updateJob'])->name('jobs.update');
});

// Deploy utilities (browser) — DEPLOY_KEY in .env. See routes/deploy.php
$registerDeployRoutes = require __DIR__ . '/deploy.php';
Route::prefix('deploy')->group($registerDeployRoutes);
Route::prefix('erp/deploy')->group($registerDeployRoutes);
