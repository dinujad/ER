<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\SmartCategorizationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class CategorizationController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $rows = Schema::hasTable('acct_categorization_suggestions')
            ? DB::table('acct_categorization_suggestions')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->limit(50)
                ->get()
            : collect();

        return view('accounting::phase5.categorization.index', [
            'pageTitle' => 'Categorization Review',
            'pageSub' => 'Suggestions require approval',
            'suggestions' => $rows,
        ]);
    }

    public function suggest(Request $request, SmartCategorizationService $service)
    {
        $this->requireManage();
        $request->validate([
            'description' => 'required|string|max:500',
            'amount' => 'nullable|numeric',
            'supplier_id' => 'nullable|integer',
        ]);

        $result = $service->suggest($this->sessionBusinessId(), 'manual', null, $request->only([
            'description', 'amount', 'supplier_id', 'cost_center_id', 'project_id',
        ]));

        return redirect('accounting/phase5/categorization')->with('status', 'Suggestion #'.($result['suggestion_id'] ?? '').' created (not posted).');
    }

    public function decide(int $id, Request $request, SmartCategorizationService $service)
    {
        $this->requireManage();
        $request->validate(['decision' => 'required|in:accepted,rejected']);
        $service->decide(
            $this->sessionBusinessId(),
            $id,
            $request->input('decision'),
            Auth::id(),
            $request->input('note'),
            $request->integer('account_id') ?: null
        );

        return redirect('accounting/phase5/categorization')->with('status', 'Decision saved.');
    }
}
