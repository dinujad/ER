<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AcctCostAllocation;
use App\Models\Accounting\AcctCostCenter;
use App\Services\Accounting\CostCenterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class CostCenterController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $centers = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->with('parent')->orderBy('code')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        return view('accounting::phase3.cost_centers.index', compact('centers'));
    }

    public function create()
    {
        $parents = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->where('status', 'active')->orderBy('code')->get()
            : collect();

        return view('accounting::phase3.cost_centers.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer',
        ]);

        try {
            CostCenterService::create($data, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/cost-centers');
    }

    public function edit($id)
    {
        $center = AcctCostCenter::findOrFail($id);
        $parents = AcctCostCenter::query()->where('id', '!=', $id)->where('status', 'active')->orderBy('code')->get();

        return view('accounting::phase3.cost_centers.edit', compact('center', 'parents'));
    }

    public function update(Request $request, $id)
    {
        $this->requireManage();
        $center = AcctCostCenter::findOrFail($id);
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        if (! empty($data['parent_id'])) {
            CostCenterService::assertNoCircularParent((int) $center->id, (int) $data['parent_id']);
        }

        $center->update($data);
        (new FlashService())->onSave();

        return redirect('accounting/cost-centers');
    }

    public function allocationsIndex()
    {
        $allocations = Schema::hasTable('acct_cost_allocations')
            ? AcctCostAllocation::query()->with('lines')->orderByDesc('allocation_date')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        return view('accounting::phase3.allocations.index', compact('allocations'));
    }

    public function allocationsCreate()
    {
        $centers = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->where('status', 'active')->orderBy('code')->get()
            : collect();

        return view('accounting::phase3.allocations.create', compact('centers'));
    }

    public function allocationsStore(Request $request)
    {
        $this->requireManage();
        $header = $request->validate([
            'name' => 'required|string|max:160',
            'allocation_date' => 'required|date',
            'source_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $linesInput = $request->input('lines', []);
        $lines = [];
        foreach ($linesInput as $line) {
            if (empty($line['cost_center_id']) || ! isset($line['amount'])) {
                continue;
            }
            $lines[] = [
                'cost_center_id' => (int) $line['cost_center_id'],
                'amount' => $line['amount'],
            ];
        }

        if (count($lines) < 1) {
            return (new FlashService())->onException(new \RuntimeException('Add at least one allocation line.'))->redirectBackWithInput();
        }

        try {
            CostCenterService::createAllocation($header, $lines, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/cost-allocations');
    }
}