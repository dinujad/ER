<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaAsset;
use App\Services\Accounting\AssetDisposalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class AssetDisposalController extends Controller
{
    use RequiresAccountingPermission;

    public function create(Request $request)
    {
        $asset = null;
        if ($request->get('asset_id') && Schema::hasTable('fa_assets')) {
            $asset = FaAsset::with('category')->find($request->get('asset_id'));
        }
        $assets = Schema::hasTable('fa_assets')
            ? FaAsset::query()->whereIn('status', ['active', 'fully_depreciated'])->orderBy('asset_code')->limit(200)->get()
            : collect();

        return view('accounting::phase3.disposals.create', compact('asset', 'assets'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'asset_id' => 'required|integer',
            'disposal_type' => 'required|in:sale,scrap,write_off,donation',
            'disposal_date' => 'required|date',
            'proceeds' => 'nullable|numeric|min:0',
        ]);

        $asset = FaAsset::findOrFail($data['asset_id']);

        try {
            $disposal = AssetDisposalService::dispose(
                $asset,
                $data['disposal_type'],
                $data['disposal_date'],
                (float) ($data['proceeds'] ?? 0),
                Auth::id()
            );
            (new FlashService())->onSave();

            return redirect('accounting/fixed-assets/'.$disposal->asset_id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }
}