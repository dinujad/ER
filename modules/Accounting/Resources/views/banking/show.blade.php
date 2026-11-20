@extends('accounting::phase2._page')

@php
    $pageTitle = $acc->display_name;
    $pageSub = $acc->bank_name . ' · ' . (\App\Services\Accounting\BankAccountService::TYPES[$acc->account_type] ?? $acc->account_type) . ' · ' . $acc->masked . ' · GL ' . $acc->gl_code;
    $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 2);
    $archived = $acc->status === 'archived';
    $tabs = ['overview' => 'Overview', 'import' => 'Import statement', 'review' => 'Review transactions', 'reconcile' => 'Reconcile', 'history' => 'History', 'settings' => 'Settings'];
@endphp

@section('page_actions')
    <a href="{{ url('accounting/banking') }}" class="btn btn-outline-secondary btn-sm">All bank accounts</a>
@endsection

@section('page_body')
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
@if($archived)<div class="alert alert-secondary py-2">Archived {{ $acc->archived_at ? \Illuminate\Support\Carbon::parse($acc->archived_at)->format('d M Y') : '' }}. History stays in the reports; new imports are blocked.</div>@endif

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-muted">Book balance</div><div class="fs-5 fw-bold">{{ $fmt($acc->book_balance) }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-muted">Latest statement{{ $acc->statement_date ? ' · ' . \Illuminate\Support\Carbon::parse($acc->statement_date)->format('d M Y') : '' }}</div><div class="fs-5 fw-bold">{{ $fmt($acc->statement_balance) }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-muted">Bank lines to match</div><div class="fs-5 fw-bold {{ $acc->unmatched ? 'text-warning' : 'text-success' }}">{{ $acc->unmatched }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-muted">Last reconciled</div><div class="fs-5 fw-bold">{{ $acc->last_reconciled ? \Illuminate\Support\Carbon::parse($acc->last_reconciled)->format('d M Y') : 'never' }}</div></div></div></div>
</div>

<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $k => $label)
        <li class="nav-item">
            @if($k === 'review')
                <a class="nav-link" href="{{ url('accounting/phase7/reconcile?account_id=' . $acc->chart_of_account_id) }}">{{ $label }}@if($acc->unmatched) <span class="badge bg-warning text-dark">{{ $acc->unmatched }}</span>@endif</a>
            @else
                <a class="nav-link {{ $tab === $k ? 'active' : '' }}" href="{{ url('accounting/banking/' . $acc->id . '?tab=' . $k) }}">{{ $label }}</a>
            @endif
        </li>
    @endforeach
</ul>

@if($tab === 'overview')
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-body">
            <div class="fw-semibold mb-2">Next steps</div>
            <div class="d-grid gap-2">
                <a class="btn btn-primary text-start" href="{{ url('accounting/banking/' . $acc->id . '?tab=import') }}"><i class="bi bi-upload me-2"></i>1. Import the latest statement</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('accounting/phase7/reconcile?account_id=' . $acc->chart_of_account_id) }}"><i class="bi bi-check2-square me-2"></i>2. Review &amp; match {{ $acc->unmatched }} bank line(s)</a>
                <a class="btn btn-outline-primary text-start" href="{{ url('accounting/banking/' . $acc->id . '?tab=reconcile') }}"><i class="bi bi-calculator me-2"></i>3. Reconcile to the statement balance</a>
                <a class="btn btn-outline-secondary text-start" href="{{ url('accounting/phase6/account-transactions?account_id=' . $acc->chart_of_account_id) }}"><i class="bi bi-journal-text me-2"></i>All ledger transactions (GL {{ $acc->gl_code }})</a>
            </div>
        </div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><strong>Statements imported</strong></div><div class="card-body p-0">
            <table class="table table-sm mb-0"><tbody>
            @forelse($imports as $i)
                <tr><td class="small">{{ $i->original_filename }}</td>
                    <td class="small text-muted">{{ $i->statement_from }} → {{ $i->statement_to }}</td>
                    <td class="text-end small">{{ $i->row_count }} lines</td>
                    <td class="text-end font-monospace small">{{ $i->closing_balance !== null ? number_format((float) $i->closing_balance, 2) : '' }}</td></tr>
            @empty
                <tr><td class="small text-muted">No statements yet.</td></tr>
            @endforelse
            </tbody></table>
        </div></div></div>
    </div>
@endif

@if($tab === 'import')
    @if($archived)
        <div class="text-muted">Restore the account to import statements.</div>
    @else
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/import/preview') }}" enctype="multipart/form-data">@csrf
            <div class="row g-2">
                <div class="col-md-5"><label class="form-label small mb-0">Statement file (CSV / XLSX)</label><input type="file" name="file" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><label class="form-label small mb-0">Statement end date</label><input type="date" name="statement_to" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small mb-0">Closing balance on the statement</label><input type="number" step="0.01" name="closing_balance" class="form-control form-control-sm" placeholder="optional, used for reconciliation"></div>
            </div>
            <div class="mt-3 small fw-semibold">Columns <span class="fw-normal text-muted">— column numbers in the file (1 = first).
                {{ $mapping ? 'Saved layout for this account is used when left empty.' : 'Leave empty to detect from the header (Date, Description, Debit, Credit, Amount).' }}</span></div>
            <div class="row g-2 mt-1">
                @foreach(['date' => 'Date', 'description' => 'Description', 'reference' => 'Reference', 'debit' => 'Withdrawal / debit', 'credit' => 'Deposit / credit', 'amount' => 'Signed amount'] as $k => $label)
                    <div class="col-4 col-md-2"><label class="form-label small mb-0">{{ $label }}</label>
                        <input type="number" min="1" max="50" name="map[{{ $k }}]" class="form-control form-control-sm" placeholder="{{ isset($mapping[$k]) && $mapping[$k] !== null ? $mapping[$k] + 1 : '' }}"></div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="form-check small"><input class="form-check-input" type="checkbox" name="save_mapping" value="1" id="sm" checked><label class="form-check-label" for="sm">Save this layout for {{ $acc->bank_name }}</label></div>
                <button class="btn btn-primary btn-sm">Preview</button>
            </div>
        </form>
    </div></div>
    @endif
@endif

@if($tab === 'reconcile')
    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="reconcile">
            <div class="col-md-3"><label class="form-label small mb-0">Statement ending date</label><input type="date" name="statement_date" value="{{ $rec['date'] ?? ($acc->statement_date ?: now()->toDateString()) }}" class="form-control form-control-sm" required></div>
            <div class="col-md-3"><label class="form-label small mb-0">Statement ending balance</label><input type="number" step="0.01" name="statement_balance" value="{{ $rec['balance'] ?? $acc->statement_balance }}" class="form-control form-control-sm" required></div>
            <div class="col-auto"><button class="btn btn-primary btn-sm">Start reconciling</button></div>
            <div class="col small text-muted">Last reconciled: {{ $lastCompleted ? \Illuminate\Support\Carbon::parse($lastCompleted->statement_date)->format('d M Y') . ' at ' . number_format((float) $lastCompleted->statement_balance, 2) : 'never' }}</div>
        </form>
    </div></div>

    @if($rec)
    <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/reconcile') }}" id="recForm">@csrf
        <input type="hidden" name="statement_date" value="{{ $rec['date'] }}">
        <input type="hidden" name="statement_balance" value="{{ $rec['balance'] }}">
        <div class="card mb-2 sticky-top" style="top:0;z-index:5"><div class="card-body py-2 d-flex flex-wrap gap-4 align-items-center small">
            <div>Beginning balance <strong class="font-monospace">{{ number_format($rec['beginning'], 2) }}</strong></div>
            <div>+ Cleared <strong class="font-monospace" id="recCleared">0.00</strong></div>
            <div>= <strong class="font-monospace" id="recBook">0.00</strong></div>
            <div>Statement <strong class="font-monospace">{{ number_format($rec['balance'], 2) }}</strong></div>
            <div class="fs-6">Difference <strong class="font-monospace" id="recDiff">—</strong></div>
            <button class="btn btn-success btn-sm ms-auto" id="recFinish" disabled>Finish reconciliation</button>
        </div></div>
        <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr>
                <th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.rec-sel').forEach(c => c.checked = this.checked); window.recUpd();"></th>
                <th>Date</th><th>Description</th><th>Source</th><th class="text-end">Money out</th><th class="text-end">Money in</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($rec['lines'] as $l)
                <tr>
                    <td><input type="checkbox" class="form-check-input rec-sel" name="cleared[]" value="{{ $l->id }}" data-amount="{{ $l->signed }}" @checked($l->on_statement)></td>
                    <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($l->date)->format('d M Y') }}</td>
                    <td class="small">{{ $l->name }}@if($l->reference)<div class="text-muted">{{ $l->reference }}</div>@endif</td>
                    <td class="small">@if($l->source['url'])<a href="{{ $l->source['url'] }}" target="_blank">{{ $l->source['label'] }}</a>@else{{ $l->source['label'] }}@endif</td>
                    <td class="text-end font-monospace">{{ $l->signed < 0 ? number_format(-$l->signed, 2) : '' }}</td>
                    <td class="text-end font-monospace">{{ $l->signed > 0 ? number_format($l->signed, 2) : '' }}</td>
                    <td class="small">@if($l->on_statement)<span class="badge bg-success-subtle text-success border">on statement</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted small">No uncleared ledger lines up to this date.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)"></textarea>
    </form>
    <script>
    (function () {
        var begin = {{ json_encode($rec['beginning']) }}, stmt = {{ json_encode($rec['balance']) }};
        window.recUpd = function () {
            var c = 0; document.querySelectorAll('.rec-sel').forEach(function (b) { if (b.checked) c += parseFloat(b.dataset.amount); });
            c = Math.round(c * 100) / 100; var book = Math.round((begin + c) * 100) / 100, d = Math.round((stmt - book) * 100) / 100;
            document.getElementById('recCleared').textContent = c.toFixed(2);
            document.getElementById('recBook').textContent = book.toFixed(2);
            var el = document.getElementById('recDiff'); el.textContent = d.toFixed(2); el.className = 'font-monospace ' + (d === 0 ? 'text-success' : 'text-danger');
            document.getElementById('recFinish').disabled = d !== 0;
        };
        document.getElementById('recForm').addEventListener('change', window.recUpd); window.recUpd();
    })();
    </script>
    <div class="small text-muted mt-2 d-flex align-items-center gap-2">Something on the statement is not in the list? <a href="{{ url('accounting/phase7/reconcile?account_id=' . $acc->chart_of_account_id) }}" class="btn btn-outline-primary btn-xs"><i class="bi bi-check2-square"></i> Review transactions</a></div>
    @endif
@endif

@if($tab === 'history')
    <div class="card"><div class="card-body p-0">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th>Statement date</th><th class="text-end">Beginning</th><th class="text-end">Cleared</th><th class="text-end">Ending balance</th><th class="text-end">Lines</th><th>By</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($reconciliations as $r)
            <tr class="{{ $r->status === 'undone' ? 'text-muted text-decoration-line-through' : '' }}">
                <td class="fw-semibold">{{ \Illuminate\Support\Carbon::parse($r->statement_date)->format('d M Y') }}</td>
                <td class="text-end font-monospace">{{ number_format((float) $r->beginning_balance, 2) }}</td>
                <td class="text-end font-monospace">{{ number_format((float) $r->cleared_total, 2) }}</td>
                <td class="text-end font-monospace fw-semibold">{{ number_format((float) $r->statement_balance, 2) }}</td>
                <td class="text-end">{{ $r->cleared_count }}</td>
                <td class="small">{{ $users[$r->completed_by] ?? '—' }}<div class="text-muted">{{ $r->completed_at ? \Illuminate\Support\Carbon::parse($r->completed_at)->format('Y-m-d H:i') : '' }}</div></td>
                <td><span class="badge {{ $r->status === 'completed' ? 'bg-success' : 'bg-secondary' }}">{{ $r->status }}</span></td>
                <td class="text-end"><div class="acct-actions"><a href="{{ url('accounting/banking/' . $acc->id . '/reconciliations/' . $r->id) }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-eye"></i> View</a>@if($lastCompleted && $lastCompleted->id === $r->id)
                    <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/reconciliations/' . $r->id . '/undo') }}" onsubmit="return confirm('Undo this reconciliation? Its lines become uncleared.');">@csrf<button class="btn btn-outline-danger btn-xs"><i class="bi bi-arrow-counterclockwise"></i> Undo</button></form>
                @endif</div></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted small p-3">No reconciliations yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div></div>
@endif

@if($tab === 'settings')
    <div class="row g-3">
        <div class="col-lg-7"><div class="card"><div class="card-body">
            <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/settings') }}">@csrf
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small mb-0">Display name</label><input name="display_name" value="{{ $acc->display_name }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-6"><label class="form-label small mb-0">Bank</label><input name="bank_name" value="{{ $acc->bank_name }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-6"><label class="form-label small mb-0">Account number</label><input name="account_number" value="{{ $acc->account_number }}" class="form-control form-control-sm"></div>
                    <div class="col-md-6 small text-muted d-flex align-items-end">Ledger: {{ $ledger?->gl_code }} {{ $ledger?->name }} ({{ $ledger?->account_type }})</div>
                </div>
                <div class="mt-3 small fw-semibold">Import column layout <span class="fw-normal text-muted">(1 = first column; empty keeps the saved value)</span></div>
                <div class="row g-2 mt-1">
                    @foreach(['date' => 'Date', 'description' => 'Description', 'reference' => 'Reference', 'debit' => 'Debit', 'credit' => 'Credit', 'amount' => 'Amount'] as $k => $label)
                        <div class="col-4 col-md-2"><label class="form-label small mb-0">{{ $label }}</label>
                            <input type="number" min="1" max="50" name="map[{{ $k }}]" class="form-control form-control-sm" placeholder="{{ isset($mapping[$k]) && $mapping[$k] !== null ? $mapping[$k] + 1 : '—' }}"></div>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-sm mt-3">Save settings</button>
            </form>
        </div></div></div>
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-body small">
                <div class="fw-semibold">Statement source</div>
                @if($acc->statement_source === 'feed')
                    <div>Bank feed connected ({{ $acc->feed_status }}).</div>
                    <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/disconnect-feed') }}" class="mt-2">@csrf<button class="btn btn-outline-danger btn-sm">Disconnect feed</button></form>
                @else
                    <div class="text-muted">Manual import (CSV / XLSX). Bank feeds are not available yet for Sri Lankan banks.</div>
                @endif
            </div></div>
            <div class="card border-danger"><div class="card-body small">
                @if($archived)
                    <div class="fw-semibold">Archived account</div>
                    <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/restore') }}" class="mt-2">@csrf<button class="btn btn-outline-primary btn-sm">Restore</button></form>
                @else
                    <div class="fw-semibold">Close this account</div>
                    <div class="text-muted mb-2">Reconcile the final statement first. Archiving hides the account from lists and imports; its ledger history stays in all reports.</div>
                    <form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/archive') }}" onsubmit="return confirm('Archive {{ $acc->display_name }}?');">@csrf
                        @if(abs((float) $acc->book_balance) > 0.009)
                            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="accept_balance" value="1" id="ab"><label class="form-check-label" for="ab">Archive with book balance {{ number_format((float) $acc->book_balance, 2) }}</label></div>
                        @endif
                        <button class="btn btn-outline-danger btn-sm">Archive account</button>
                    </form>
                @endif
            </div></div>
        </div>
    </div>
@endif
@endsection
