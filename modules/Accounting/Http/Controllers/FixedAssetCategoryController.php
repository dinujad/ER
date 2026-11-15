<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaAssetCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class FixedAssetCategoryController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $categories = Schema::hasTable('fa_asset_categories')
            ? FaAssetCategory::query()->orderBy('code')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        $accounts = Schema::hasTable('chart_of_accounts')
            ? ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get()
            : collect();

        return view('accounting::phase3.categories.index', compact('categories', 'accounts'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:160',
            'default_depreciation_method' => 'required|in:straight_line,declining_balance,none',
            'default_useful_life_months' => 'required|integer|min:1|max:600',
            'default_salvage_value' => 'nullable|numeric|min:0',
            'asset_account_id' => 'nullable|integer',
            'accumulated_depreciation_account_id' => 'nullable|integer',
            'depreciation_expense_account_id' => 'nullable|integer',
            'disposal_gain_account_id' => 'nullable|integer',
            'disposal_loss_account_id' => 'nullable|integer',
        ]);

        FaAssetCategory::create(array_merge($data, [
            'business_id' => 0,
            'default_salvage_value' => $data['default_salvage_value'] ?? 0,
            'active' => true,
        ]));

        (new FlashService())->onSave();

        return redirect('accounting/fixed-asset-categories');
    }

    public function update(Request $request, $id)
    {
        $this->requireManage();
        $category = FaAssetCategory::findOrFail($id);
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:160',
            'default_depreciation_method' => 'required|in:straight_line,declining_balance,none',
            'default_useful_life_months' => 'required|integer|min:1|max:600',
            'default_salvage_value' => 'nullable|numeric|min:0',
            'asset_account_id' => 'nullable|integer',
            'accumulated_depreciation_account_id' => 'nullable|integer',
            'depreciation_expense_account_id' => 'nullable|integer',
            'disposal_gain_account_id' => 'nullable|integer',
            'disposal_loss_account_id' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        $category->update($data);
        (new FlashService())->onSave();

        return redirect('accounting/fixed-asset-categories');
    }
}