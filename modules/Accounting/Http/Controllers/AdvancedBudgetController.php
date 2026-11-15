<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AcctBudgetVersion;
use App\Models\Accounting\AcctBudgetVersionLine;
use App\Models\Accounting\AcctCostCenter;
use App\Services\Accounting\AdvancedBudgetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class AdvancedBudgetController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $versions = Schema::hasTable('acct_budget_versions')
            ? AcctBudgetVersion::query()->orderByDesc('financial_year')->orderByDesc('version_no')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        return view('accounting::phase3.budgets.index', compact('versions'));
    }

    public function create()
    {
        $accounts = Schema::hasTable('chart_of_accounts')
            ? ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get()
            : collect();
        $costCenters = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->where('status', 'active')->orderBy('code')->get()
            : collect();
        $nextVersion = (int) (AcctBudgetVersion::query()->max('version_no') ?? 0) + 1;

        return view('accounting::phase3.budgets.create', compact('accounts', 'costCenters', 'nextVersion'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $header = $request->validate([
            'name' => 'required|string|max:160',
            'financial_year' => 'required|string|max:10',
            'version_no' => 'nullable|integer|min:1',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $lines = [];
        foreach ($request->input('lines', []) as $line) {
            if (empty($line['chart_of_account_id'])) {
                continue;
            }
            $lines[] = $line;
        }
        if ($lines === []) {
            return (new FlashService())->onException(new \RuntimeException('Add at least one budget line.'))->redirectBackWithInput();
        }

        try {
            $version = AdvancedBudgetService::createVersion($header, $lines, Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/budget-versions/'.$version->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function show($id)
    {
        $version = AcctBudgetVersion::with('lines')->findOrFail($id);

        return view('accounting::phase3.budgets.show', compact('version'));
    }

    public function submit($id)
    {
        $this->requireManage();
        try {
            AdvancedBudgetService::submit(AcctBudgetVersion::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/budget-versions/'.$id);
    }

    public function approve($id)
    {
        $this->requireManage();
        try {
            AdvancedBudgetService::approve(AcctBudgetVersion::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/budget-versions/'.$id);
    }

    public function activate($id)
    {
        $this->requireManage();
        try {
            AdvancedBudgetService::activate(AcctBudgetVersion::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/budget-versions/'.$id);
    }

    public function revise($id)
    {
        $this->requireManage();
        $source = AcctBudgetVersion::with('lines')->findOrFail($id);

        $version = DB::transaction(function () use ($source) {
            $maxVer = (int) AcctBudgetVersion::query()
                ->where('financial_year', $source->financial_year)
                ->max('version_no');

            $newVersion = AcctBudgetVersion::create([
                'business_id' => $source->business_id,
                'budget_id' => $source->budget_id,
                'version_no' => $maxVer + 1,
                'name' => $source->name.' (rev '.($maxVer + 1).')',
                'financial_year' => $source->financial_year,
                'status' => 'draft',
                'cost_center_id' => $source->cost_center_id,
                'department_id' => $source->department_id,
                'job_id' => $source->job_id,
                'notes' => 'Revised from version #'.$source->version_no,
                'created_by' => Auth::id(),
            ]);

            foreach ($source->lines as $line) {
                AcctBudgetVersionLine::create(array_merge(
                    $line->only([
                        'chart_of_account_id', 'annual_total',
                        'month_1', 'month_2', 'month_3', 'month_4', 'month_5', 'month_6',
                        'month_7', 'month_8', 'month_9', 'month_10', 'month_11', 'month_12',
                    ]),
                    ['budget_version_id' => $newVersion->id]
                ));
            }

            return $newVersion;
        });

        (new FlashService())->onSave();

        return redirect('accounting/budget-versions/'.$version->id);
    }

    public function vsActual(Request $request)
    {
        $versionId = (int) $request->get('version_id');
        $month = (int) $request->get('month', (int) date('n'));
        $report = null;
        $versions = Schema::hasTable('acct_budget_versions')
            ? AcctBudgetVersion::query()->orderByDesc('id')->limit(100)->get()
            : collect();

        if ($versionId > 0 && Schema::hasTable('acct_budget_versions')) {
            $version = AcctBudgetVersion::with('lines')->find($versionId);
            if ($version) {
                try {
                    $report = AdvancedBudgetService::budgetVsActual($version, $month);
                } catch (\Throwable $e) {
                    $report = ['error' => $e->getMessage()];
                }
            }
        }

        return view('accounting::phase3.budgets.vs_actual', compact('versions', 'versionId', 'month', 'report'));
    }
}