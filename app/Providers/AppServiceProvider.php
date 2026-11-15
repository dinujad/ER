<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\MaterialIssue;
use App\Models\MaterialStockMovement;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SalesPersonExpense;
use App\Models\VehicleDailyRunItem;
use App\Models\StockLedger;
use App\Observers\ExpenseObserver;
use App\Observers\VehicleDailyRunItemObserver;
use App\Observers\MaterialIssueAccountingObserver;
use App\Observers\MaterialStockMovementAccountingObserver;
use App\Observers\ManufacturingAdditionalCostAccountingObserver;
use App\Observers\PaymentObserver;
use App\Observers\PayrollRunItemObserver;
use App\Observers\PayrollRunObserver;
use App\Observers\PurchaseObserver;
use App\Observers\PurchasePaymentObserver;
use App\Observers\PurchaseReturnObserver;
use App\Observers\SalesPersonExpenseObserver;
use App\Observers\StockLedgerAccountingObserver;
use App\Support\ModuleManager;
use App\Support\NoopActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf as DomPdfFacade;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('module.manager', fn () => new ModuleManager());
        $this->app->singleton(NoopActivityLogger::class, fn () => new NoopActivityLogger());
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Blade::if('canRoute', fn (string $name) =>
            \App\Support\ActionPermissions::allowsRoute(auth()->user(), $name));
        date_default_timezone_set((string) config('app.timezone', 'Asia/Colombo'));

        // Include accounting module migrations so `php artisan migrate` picks them up
        $this->loadMigrationsFrom(base_path('modules/Accounting/Database/Migrations'));

        Payment::observe(PaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        Purchase::observe(PurchaseObserver::class);
        PurchasePayment::observe(PurchasePaymentObserver::class);
        PurchaseReturn::observe(PurchaseReturnObserver::class);
        SalesPersonExpense::observe(SalesPersonExpenseObserver::class);
        PayrollRun::observe(PayrollRunObserver::class);
        PayrollRunItem::observe(PayrollRunItemObserver::class);
        MaterialIssue::observe(MaterialIssueAccountingObserver::class);
        MaterialStockMovement::observe(MaterialStockMovementAccountingObserver::class);
        ManufacturingJobAdditionalCost::observe(ManufacturingAdditionalCostAccountingObserver::class);
        StockLedger::observe(StockLedgerAccountingObserver::class);
        VehicleDailyRunItem::observe(VehicleDailyRunItemObserver::class);

        Paginator::useBootstrapFive();

        // Register Module:: facade alias for accounting module views
        if (!class_exists('Module')) {
            class_alias(\App\Facades\Module::class, 'Module');
        }

        // Accounting controllers use legacy `use PDF;` imports (global alias).
        if (!class_exists('PDF', false)) {
            class_alias(DomPdfFacade::class, 'PDF');
        }

        // Bridge Accounting module's @can checks to Dermas role permissions.
        Gate::before(function ($user, $ability) {
            if (!$user) {
                return null;
            }
            try {
                if (request()->is('accounting*', 'report/accounting*')) {
                    if ($user->isSystemAdmin()) {
                        return true;
                    }

                    $required = match (true) {
                        str_contains((string) $ability, 'delete') => 'accounting.delete',
                        str_contains((string) $ability, 'edit') => 'accounting.edit',
                        str_contains((string) $ability, 'create'),
                        str_contains((string) $ability, 'reverse') => 'accounting.manage',
                        default => 'accounting.view',
                    };

                    return $user->can_access($required);
                }
            } catch (\Throwable) {
                return null;
            }

            return null;
        });

        \Illuminate\Support\Facades\View::share('asset_v', config('app.asset_version', time()));
    }
}
