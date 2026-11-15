@extends('erp.layout')

@section('title', 'Cheque & WhatsApp notifications')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Cheque payment reminders (SMS, email, HostGrap WhatsApp)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            When a <strong>cheque</strong> payment is added (sales portal or admin), the system can notify the customer (SMS / email) and send WhatsApp messages to the
            <strong>admin number</strong> below and the <strong>sales person</strong> (uses the phone on the linked employee record — Employees → contact).
            On the <strong>cheque date</strong>, a follow-up WhatsApp reminder is sent once (scheduler / <code>php artisan cheque:send-due-reminders</code>).
            HostGrap API: <a href="https://wa-api.hostgrap.com" target="_blank" rel="noopener">wa-api.hostgrap.com</a>.
            Cheque scans are sent with <strong>send-image</strong> (HostGrap downloads your <code>APP_URL</code> signed image link), so production must use a <strong>public HTTPS</strong> base URL.
            Store the API key here (encrypted in the database) or use <code>.env</code> keys <code>HOSTGRAP_WHATSAPP_EMAIL</code>, <code>HOSTGRAP_WHATSAPP_API_KEY</code>, <code>HOSTGRAP_ADMIN_WHATSAPP_PHONE</code> as fallbacks.
        </p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @canRoute('erp.settings.cheque-notifications.update')
<form method="POST" action="{{ route('erp.settings.cheque-notifications.update') }}">
            @csrf
            @method('PUT')
            @php
                $smsOn = $errors->any() ? old('notify_customer_sms') === '1' : \App\Models\AppSetting::getBool(\App\Models\AppSetting::KEY_NOTIFY_CUSTOMER_SMS, true);
                $emOn = $errors->any() ? old('notify_customer_email') === '1' : \App\Models\AppSetting::getBool(\App\Models\AppSetting::KEY_NOTIFY_CUSTOMER_EMAIL, true);
                $adOn = $errors->any() ? old('notify_admin_whatsapp') === '1' : \App\Models\AppSetting::getBool(\App\Models\AppSetting::KEY_NOTIFY_ADMIN_WA, true);
                $spOn = $errors->any() ? old('notify_sales_whatsapp') === '1' : \App\Models\AppSetting::getBool(\App\Models\AppSetting::KEY_NOTIFY_SALES_WA, true);
            @endphp

            <h6 class="mt-3">HostGrap WhatsApp</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">API email</label>
                    <input type="email" name="hostgrap_email" class="form-control @error('hostgrap_email') is-invalid @enderror"
                        value="{{ old('hostgrap_email', \App\Models\AppSetting::get(\App\Models\AppSetting::KEY_HOSTGRAP_EMAIL) ?: config('services.hostgrap.email')) }}">
                    @error('hostgrap_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">API key</label>
                    <input type="password" name="hostgrap_api_key" class="form-control @error('hostgrap_api_key') is-invalid @enderror"
                        placeholder="{{ $hasStoredApiKey ? 'Leave blank to keep existing key' : 'Paste API key' }}" autocomplete="new-password">
                    @error('hostgrap_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($hasStoredApiKey)
                        <small class="text-muted">A key is already saved (encrypted). Enter a new value only to replace it.</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin WhatsApp number</label>
                    <input type="text" name="hostgrap_admin_phone" class="form-control @error('hostgrap_admin_phone') is-invalid @enderror"
                        value="{{ old('hostgrap_admin_phone', \App\Models\AppSetting::get(\App\Models\AppSetting::KEY_HOSTGRAP_ADMIN_PHONE) ?: config('services.hostgrap.admin_phone')) }}"
                        placeholder="947XXXXXXXX or 07XXXXXXXX">
                    <small class="text-muted">Receives new-cheque alerts and cheque-date reminders.</small>
                    @error('hostgrap_admin_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h6 class="mt-4">Channels</h6>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="notify_customer_sms" value="1" id="n_sms" @checked($smsOn)>
                <label class="form-check-label" for="n_sms">SMS to customer (Text.lk — needs <code>TEXTLK_API_KEY</code>)</label>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="notify_customer_email" value="1" id="n_em" @checked($emOn)>
                <label class="form-check-label" for="n_em">Email to customer (when email is on the customer record)</label>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="notify_admin_whatsapp" value="1" id="n_ad" @checked($adOn)>
                <label class="form-check-label" for="n_ad">WhatsApp to admin number (above)</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="notify_sales_whatsapp" value="1" id="n_sp" @checked($spOn)>
                <label class="form-check-label" for="n_sp">WhatsApp to sales person (employee contact)</label>
            </div>

            <h6 class="mt-4">Auto customer reminder (scheduler)</h6>
            <p class="small text-muted">The scheduler runs <code>php artisan cheque:send-due-reminders</code> daily at 08:00. It will automatically send a reminder to the customer <strong>X days before</strong> the cheque date (configurable below). Choose which channels to use for that auto-reminder.</p>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label">Days before cheque date to remind customer</label>
                    <input type="number" name="customer_remind_days_before" class="form-control" min="0" max="60"
                        value="{{ old('customer_remind_days_before', \App\Models\AppSetting::get(\App\Models\AppSetting::KEY_CUSTOMER_REMIND_DAYS_BEFORE, '3')) }}">
                    <small class="text-muted">Set 0 to disable auto customer reminder.</small>
                </div>
                <div class="col-md-8">
                    <label class="form-label d-block">Channels for auto customer reminder</label>
                    @php
                        $autoCh = explode(',', \App\Models\AppSetting::get(\App\Models\AppSetting::KEY_CUSTOMER_REMIND_CHANNELS, 'whatsapp,sms,email'));
                        $autoCh = array_map('trim', $autoCh);
                    @endphp
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="auto_ch_whatsapp" value="1" id="ach_wa" @checked(in_array('whatsapp', $autoCh))>
                        <label class="form-check-label" for="ach_wa">WhatsApp</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="auto_ch_sms" value="1" id="ach_sms" @checked(in_array('sms', $autoCh))>
                        <label class="form-check-label" for="ach_sms">SMS</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="auto_ch_email" value="1" id="ach_email" @checked(in_array('email', $autoCh))>
                        <label class="form-check-label" for="ach_email">Email</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save settings</button>
        </form>
@endcanRoute
    </div>
</div>
@endsection
