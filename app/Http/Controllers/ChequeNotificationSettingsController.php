<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class ChequeNotificationSettingsController extends Controller
{
    public function edit()
    {
        $this->ensureNotSalesPortalOnlyUser();

        $hasStoredApiKey = filled(AppSetting::get(AppSetting::KEY_HOSTGRAP_API_KEY_ENC));

        return view('erp.settings.cheque_notifications', compact('hasStoredApiKey'));
    }

    public function update(Request $request)
    {
        $this->ensureNotSalesPortalOnlyUser();

        $request->validate([
            'hostgrap_email' => 'nullable|email|max:255',
            'hostgrap_api_key' => 'nullable|string|max:500',
            'hostgrap_admin_phone' => 'nullable|string|max:30',
        ]);

        AppSetting::put(AppSetting::KEY_HOSTGRAP_EMAIL, $request->input('hostgrap_email'));
        AppSetting::put(AppSetting::KEY_HOSTGRAP_ADMIN_PHONE, $request->input('hostgrap_admin_phone'));

        AppSetting::put(AppSetting::KEY_NOTIFY_CUSTOMER_SMS, $request->boolean('notify_customer_sms') ? '1' : '0');
        AppSetting::put(AppSetting::KEY_NOTIFY_CUSTOMER_EMAIL, $request->boolean('notify_customer_email') ? '1' : '0');
        AppSetting::put(AppSetting::KEY_NOTIFY_ADMIN_WA, $request->boolean('notify_admin_whatsapp') ? '1' : '0');
        AppSetting::put(AppSetting::KEY_NOTIFY_SALES_WA, $request->boolean('notify_sales_whatsapp') ? '1' : '0');

        if ($request->filled('hostgrap_api_key')) {
            AppSetting::putEncryptedApiKey($request->input('hostgrap_api_key'));
        }

        // Auto customer reminder settings
        $daysBefore = max(0, (int) $request->input('customer_remind_days_before', 3));
        AppSetting::put(AppSetting::KEY_CUSTOMER_REMIND_DAYS_BEFORE, (string) $daysBefore);

        $autoCh = [];
        if ($request->boolean('auto_ch_whatsapp')) $autoCh[] = 'whatsapp';
        if ($request->boolean('auto_ch_sms'))      $autoCh[] = 'sms';
        if ($request->boolean('auto_ch_email'))    $autoCh[] = 'email';
        AppSetting::put(AppSetting::KEY_CUSTOMER_REMIND_CHANNELS, implode(',', $autoCh) ?: 'whatsapp,sms,email');

        return back()->with('success', 'Cheque / WhatsApp notification settings saved.');
    }

    protected function ensureNotSalesPortalOnlyUser(): void
    {
        $user = auth()->user();
        if ($user && $user->role === 'sales_person') {
            abort(403);
        }
    }
}
