@extends('accounting::phase2._page')
@section('page_body')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="alert alert-info small">
    API keys are stored only in server <code>.env</code> — never in this form, logs, or browser responses.<br>
    <strong>Gemini:</strong> set <code>ACCOUNTING_AI_GEMINI_API_KEY</code>, choose provider <code>gemini</code>, enable below.
    Optional: <code>ACCOUNTING_AI_GEMINI_MODEL</code> (default <code>gemini-2.0-flash</code>),
    <code>ACCOUNTING_AI_ENABLED=true</code>.
</div>
<form method="post" action="{{ url('accounting/phase5/settings') }}">@csrf
    <div class="mb-2">
        <label class="form-label">Provider</label>
        <select name="provider" class="form-select" id="aiProviderSelect">
            @foreach($providers as $p)
                <option value="{{ $p }}" @selected(($settings['provider'] ?? '')===$p)>{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-2">
        <label class="form-label">Model</label>
        <input name="model" id="aiModelInput" class="form-control" value="{{ $settings['model'] ?? '' }}" placeholder="e.g. gemini-2.0-flash">
        <div class="form-text">Gemini examples: gemini-2.0-flash, gemini-1.5-flash, gemini-1.5-pro</div>
    </div>
    <div class="mb-2">
        <label class="form-label">Endpoint (optional)</label>
        <input name="endpoint" id="aiEndpointInput" class="form-control" value="{{ $settings['endpoint'] ?? '' }}" placeholder="Leave blank for provider default">
        <div class="form-text">Gemini default: https://generativelanguage.googleapis.com/v1beta</div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col"><label class="form-label">Timeout (s)</label><input type="number" name="timeout_seconds" class="form-control" value="{{ $settings['timeout_seconds'] ?? 30 }}"></div>
        <div class="col"><label class="form-label">Max tokens</label><input type="number" name="max_tokens" class="form-control" value="{{ $settings['max_tokens'] ?? 2048 }}"></div>
        <div class="col"><label class="form-label">Monthly limit</label><input type="number" name="monthly_request_limit" class="form-control" value="{{ $settings['monthly_request_limit'] ?? 500 }}"></div>
        <div class="col"><label class="form-label">Retention days</label><input type="number" name="retention_days" class="form-control" value="{{ $settings['retention_days'] ?? 90 }}"></div>
    </div>
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="aiEnabled" @checked(!empty($settings['enabled']))>
        <label class="form-check-label" for="aiEnabled">Enable external AI provider for this business</label>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="external_disclosure_accepted" value="1" id="aiDisclosure" @checked(!empty($settings['external_disclosure_accepted']))>
        <label class="form-check-label" for="aiDisclosure">I acknowledge that enabling an external provider may send minimized financial context outside this server.</label>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
<script>
(function () {
  var select = document.getElementById('aiProviderSelect');
  var model = document.getElementById('aiModelInput');
  var endpoint = document.getElementById('aiEndpointInput');
  if (!select || !model) return;
  select.addEventListener('change', function () {
    if (select.value === 'gemini') {
      if (!model.value) model.value = 'gemini-2.0-flash';
      if (endpoint && !endpoint.value) endpoint.placeholder = 'https://generativelanguage.googleapis.com/v1beta';
    }
  });
})();
</script>
@endsection
