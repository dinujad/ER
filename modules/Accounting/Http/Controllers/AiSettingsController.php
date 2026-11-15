<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\AiProviderManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class AiSettingsController extends Controller
{
    use RequiresAccountingPermission;

    public function index(AiProviderManager $manager)
    {
        $settings = $manager->businessSettings($this->sessionBusinessId());
        // Never expose API keys
        unset($settings['api_key']);

        return view('accounting::phase5.settings.index', [
            'pageTitle' => 'AI Settings',
            'pageSub' => 'Provider · limits · privacy',
            'settings' => $settings,
            'providers' => array_keys(config('accounting_ai.providers', [])),
        ]);
    }

    public function update(Request $request, AiProviderManager $manager)
    {
        $this->requireManage();
        $request->validate([
            'provider' => 'required|string|max:40',
            'model' => 'nullable|string|max:120',
            'endpoint' => 'nullable|string|max:255',
            'timeout_seconds' => 'nullable|integer|min:5|max:120',
            'max_tokens' => 'nullable|integer|min:256|max:8192',
            'monthly_request_limit' => 'nullable|integer|min:1|max:100000',
            'retention_days' => 'nullable|integer|min:1|max:3650',
            'enabled' => 'nullable|boolean',
            'external_disclosure_accepted' => 'nullable|boolean',
        ]);

        $manager->saveSettings($this->sessionBusinessId(), [
            'provider' => $request->input('provider'),
            'model' => $request->input('model'),
            'endpoint' => $request->input('endpoint'),
            'timeout_seconds' => $request->input('timeout_seconds', 30),
            'max_tokens' => $request->input('max_tokens', 2048),
            'monthly_request_limit' => $request->input('monthly_request_limit', 500),
            'retention_days' => $request->input('retention_days', 90),
            'enabled' => $request->boolean('enabled'),
            'external_disclosure_accepted' => $request->boolean('external_disclosure_accepted'),
        ], Auth::id());

        return redirect('accounting/phase5/settings')->with('status', 'AI settings saved. API keys remain in environment only.');
    }
}
