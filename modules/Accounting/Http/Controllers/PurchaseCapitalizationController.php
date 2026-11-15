<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaCapitalizationCandidate;
use App\Services\Accounting\PurchaseAssetCapitalizationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class PurchaseCapitalizationController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        if (! Schema::hasTable('fa_capitalization_candidates')) {
            return view('accounting::phase3.purchase_capitalization.index', ['candidates' => collect()]);
        }

        $q = FaCapitalizationCandidate::query()
            ->with(['purchase', 'purchaseItem', 'category', 'asset'])
            ->orderByDesc('id');

        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        $candidates = $q->paginate(25)->withQueryString();

        return view('accounting::phase3.purchase_capitalization.index', compact('candidates'));
    }

    public function show($id)
    {
        $candidate = FaCapitalizationCandidate::with(['purchase', 'purchaseItem', 'category', 'asset'])->findOrFail($id);

        return view('accounting::phase3.purchase_capitalization.show', compact('candidate'));
    }

    public function approve($id)
    {
        $this->requireManage();
        $candidate = FaCapitalizationCandidate::findOrFail($id);
        try {
            PurchaseAssetCapitalizationService::approve($candidate, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/purchase-capitalization/'.$id);
    }

    public function capitalize(Request $request, $id)
    {
        $this->requireManage();
        $request->validate(['posting_date' => 'required|date']);
        $candidate = FaCapitalizationCandidate::findOrFail($id);

        try {
            $asset = PurchaseAssetCapitalizationService::capitalize($candidate, $request->posting_date, Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/fixed-assets/'.$asset->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }
    }
}