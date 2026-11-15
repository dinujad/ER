<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class AnomalyAlertController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $alerts = Schema::hasTable('acct_anomaly_alerts')
            ? DB::table('acct_anomaly_alerts')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->limit(100)
                ->get()
            : collect();

        return view('accounting::phase5.alerts.index', [
            'pageTitle' => 'Financial Alerts',
            'pageSub' => 'Anomaly indicators · review only',
            'alerts' => $alerts,
        ]);
    }

    public function run(AnomalyDetectionService $service)
    {
        $this->requireManage();
        $alerts = $service->run($this->sessionBusinessId());

        return redirect('accounting/phase5/alerts')->with('status', count($alerts).' alert(s) evaluated.');
    }

    public function updateStatus(int $id, Request $request, AnomalyDetectionService $service)
    {
        $this->requireManage();
        $request->validate(['status' => 'required|in:new,under_review,resolved,dismissed']);
        $service->setStatus($this->sessionBusinessId(), $id, $request->input('status'), Auth::id(), $request->input('note'));

        return redirect('accounting/phase5/alerts')->with('status', 'Alert updated.');
    }
}
