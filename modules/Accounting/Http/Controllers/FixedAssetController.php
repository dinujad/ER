<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AcctCostCenter;
use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetCategory;
use App\Services\Accounting\AccountingAuditService;
use App\Services\Accounting\FixedAssetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class FixedAssetController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        if (! Schema::hasTable('fa_assets')) {
            $assets = new LengthAwarePaginator([], 0, 25);

            return view('accounting::phase3.fixed_assets.index', [
                'assets' => $assets,
                'status' => $request->get('status'),
            ]);
        }

        $q = FaAsset::query()->with('category')->orderByDesc('id');
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $request->get('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($cat = $request->get('category_id')) {
            $q->where('category_id', (int) $cat);
        }

        $assets = $q->paginate(25)->withQueryString();
        $categories = FaAssetCategory::query()->orderBy('name')->get();

        return view('accounting::phase3.fixed_assets.index', compact('assets', 'status', 'categories'));
    }

    public function create()
    {
        $categories = Schema::hasTable('fa_asset_categories')
            ? FaAssetCategory::query()->where('active', 1)->orderBy('name')->get()
            : collect();
        $costCenters = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->where('status', 'active')->orderBy('code')->get()
            : collect();
        $suggestedCode = Schema::hasTable('fa_assets') ? FixedAssetService::nextAssetCode() : '';

        return view('accounting::phase3.fixed_assets.create', compact('categories', 'costCenters', 'suggestedCode'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'category_id' => 'required|integer',
            'name' => 'required|string|max:200',
            'asset_code' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:2000',
            'purchase_date' => 'nullable|date',
            'acquisition_date' => 'nullable|date',
            'in_service_date' => 'nullable|date',
            'asset_cost' => 'required|numeric|min:0',
            'capitalized_costs' => 'nullable|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'nullable|integer|min:1',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,none',
            'cost_center_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'purchase_reference' => 'nullable|string|max:120',
            'purchase_id' => 'nullable|integer',
            'purchase_item_id' => 'nullable|integer',
        ]);

        try {
            $asset = FixedAssetService::createDraft($data, Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/fixed-assets/'.$asset->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function show($id)
    {
        $asset = FaAsset::with(['category', 'events', 'depreciationSchedules', 'disposal', 'attachments'])->findOrFail($id);

        return view('accounting::phase3.fixed_assets.show', compact('asset'));
    }

    public function edit($id)
    {
        $asset = FaAsset::findOrFail($id);
        if ($asset->status !== 'draft') {
            abort(403, 'Only draft assets can be edited.');
        }
        $categories = FaAssetCategory::query()->where('active', 1)->orderBy('name')->get();
        $costCenters = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->where('status', 'active')->orderBy('code')->get()
            : collect();

        return view('accounting::phase3.fixed_assets.edit', compact('asset', 'categories', 'costCenters'));
    }

    public function update(Request $request, $id)
    {
        $this->requireManage();
        $asset = FaAsset::findOrFail($id);
        if ($asset->status !== 'draft') {
            abort(403, 'Only draft assets can be edited.');
        }

        $data = $request->validate([
            'category_id' => 'required|integer',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'purchase_date' => 'nullable|date',
            'acquisition_date' => 'nullable|date',
            'in_service_date' => 'nullable|date',
            'asset_cost' => 'required|numeric|min:0',
            'capitalized_costs' => 'nullable|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'nullable|integer|min:1',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,none',
            'cost_center_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'purchase_reference' => 'nullable|string|max:120',
            'purchase_id' => 'nullable|integer',
            'purchase_item_id' => 'nullable|integer',
        ]);

        $before = $asset->toArray();
        $asset->update([
            ...$data,
            'net_book_value' => round((float) $data['asset_cost'] + (float) ($data['capitalized_costs'] ?? 0), 2),
        ]);
        FixedAssetService::recordEvent($asset, 'updated', 'Asset updated', $before, $asset->fresh()->toArray(), Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/fixed-assets/'.$asset->id);
    }

    public function submit($id)
    {
        $this->requireManage();
        $asset = FaAsset::findOrFail($id);
        if ($asset->status !== 'draft') {
            return (new FlashService())->onException(new \RuntimeException('Only draft assets can be submitted.'))->redirectBack();
        }
        $before = $asset->toArray();
        $asset->update(['status' => 'submitted']);
        FixedAssetService::recordEvent($asset, 'submitted', 'Submitted for approval', $before, $asset->fresh()->toArray(), Auth::id());
        AccountingAuditService::record('fa_asset.submitted', $asset, $before, $asset->fresh()->toArray(), null, (int) $asset->business_id, Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/fixed-assets/'.$asset->id);
    }

    public function approve($id)
    {
        $this->requireManage();
        $asset = FaAsset::findOrFail($id);
        if ($asset->status !== 'submitted') {
            return (new FlashService())->onException(new \RuntimeException('Only submitted assets can be approved.'))->redirectBack();
        }
        $before = $asset->toArray();
        $asset->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        FixedAssetService::recordEvent($asset, 'approved', 'Asset approved', $before, $asset->fresh()->toArray(), Auth::id());
        AccountingAuditService::record('fa_asset.approved', $asset, $before, $asset->fresh()->toArray(), null, (int) $asset->business_id, Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/fixed-assets/'.$asset->id);
    }

    public function capitalize(Request $request, $id)
    {
        $this->requireManage();
        $request->validate(['posting_date' => 'required|date']);
        $asset = FaAsset::findOrFail($id);

        if (! in_array($asset->status, ['draft', 'approved'], true)) {
            return (new FlashService())->onException(new \RuntimeException('Asset must be draft or approved to capitalize.'))->redirectBack();
        }

        try {
            FixedAssetService::capitalize($asset, $request->posting_date, $request->get('credit_gl'), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/fixed-assets/'.$id);
    }

    public function transfer(Request $request, $id)
    {
        $this->requireManage();
        $request->validate(['cost_center_id' => 'required|integer']);
        $asset = FaAsset::findOrFail($id);
        if (! in_array($asset->status, ['active', 'fully_depreciated', 'approved', 'draft', 'submitted'], true)) {
            return (new FlashService())->onException(new \RuntimeException('Asset cannot be transferred in its current status.'))->redirectBack();
        }
        $before = $asset->toArray();
        $asset->update(['cost_center_id' => (int) $request->cost_center_id]);
        FixedAssetService::recordEvent($asset, 'transferred', 'Cost center transfer', $before, $asset->fresh()->toArray(), Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/fixed-assets/'.$id);
    }

    public function deactivate($id)
    {
        $this->requireManage();
        $asset = FaAsset::findOrFail($id);
        if (! in_array($asset->status, ['active', 'fully_depreciated', 'approved'], true)) {
            return (new FlashService())->onException(new \RuntimeException('Only active or approved assets can be deactivated.'))->redirectBack();
        }
        $before = $asset->toArray();
        $asset->update(['status' => 'inactive']);
        FixedAssetService::recordEvent($asset, 'deactivated', 'Asset deactivated', $before, $asset->fresh()->toArray(), Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/fixed-assets/'.$id);
    }
}