@extends('accounting::phase2._page')

@php
    $pageTitle = 'Review transactions' . ($account ? ' — ' . $account->name : '');
    $pageSub = 'Match each bank statement line to what is in the books, or post new items';
    $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 2);
@endphp

@section('page_actions')
    <form method="GET" class="d-flex gap-2">
        <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected($a->id == $accountId)>{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </form>
    @if($bankAccount)
        <a href="{{ url('accounting/banking/' . $bankAccount->id . '?tab=import') }}" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-upload"></i> Import statement</a>
        <a href="{{ url('accounting/banking/' . $bankAccount->id . '?tab=reconcile') }}" class="btn btn-outline-primary btn-sm text-nowrap"><i class="bi bi-calculator"></i> Reconcile</a>
        <a href="{{ url('accounting/banking/' . $bankAccount->id) }}" class="btn btn-outline-secondary btn-sm text-nowrap">Account</a>
    @else
        <button class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="collapse" data-bs-target="#importBox"><i class="bi bi-upload"></i> Import statement</button>
    @endif
    <a href="{{ url('accounting/bank-reconciliation-report') }}" class="btn btn-outline-secondary btn-sm text-nowrap"><i class="bi bi-filetype-pdf"></i> Report</a>
@endsection

@section('page_body')

@if(!$ws)
    <div class="alert alert-info">Add a bank account in the chart of accounts first.</div>
@else

{{-- Import panel with saved mapping --}}
<div class="collapse {{ $ws['imports']->isEmpty() ? 'show' : '' }} mb-3" id="importBox">
<div class="card"><div class="card-body">
    <form method="POST" action="{{ url('accounting/phase7/reconcile/import') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="chart_of_account_id" value="{{ $accountId }}">
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label small mb-0">Statement file (CSV / XLSX from the bank)</label><input type="file" name="file" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label small mb-0">Statement from</label><input type="date" name="statement_from" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Statement to</label><input type="date" name="statement_to" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Opening balance</label><input type="number" step="0.01" name="opening_balance" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Closing balance</label><input type="number" step="0.01" name="closing_balance" class="form-control form-control-sm" placeholder="from statement"></div>
        </div>
        <div class="mt-3 small fw-semibold">Column mapping <span class="text-muted fw-normal">— column numbers in the bank file (1 = first column).
            @if($mapping) Saved mapping for this account is used when these are empty. @else Leave empty to auto-detect from header names (Date, Description, Debit, Credit, Amount). @endif</span></div>
        <div class="row g-2 mt-1">
            @foreach(['date' => 'Date', 'description' => 'Description', 'reference' => 'Reference', 'debit' => 'Debit / withdrawal', 'credit' => 'Credit / deposit', 'amount' => 'Signed amount'] as $k => $label)
                <div class="col-4 col-md-2">
                    <label class="form-label small mb-0">{{ $label }}</label>
                    <input type="number" min="1" max="50" name="map[{{ $k }}]" class="form-control form-control-sm" placeholder="{{ isset($mapping[$k]) && $mapping[$k] !== null ? $mapping[$k] + 1 : '' }}">
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="form-check small"><input class="form-check-input" type="checkbox" name="save_mapping" value="1" id="saveMap" checked><label class="form-check-label" for="saveMap">Save this mapping for {{ $account?->name }} (next imports use it automatically)</label></div>
            <button class="btn btn-primary btn-sm">Import</button>
        </div>
        <div class="small text-muted mt-1">Lines already imported (same date, amount and description) are skipped automatically.</div>
    </form>
</div></div>
</div>

{{-- Balances --}}
@php $diff = $ws['difference']; @endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2">
        <div class="small text-muted">Statement balance{{ $ws['latest']?->statement_to ? ' · ' . \Illuminate\Support\Carbon::parse($ws['latest']->statement_to)->format('d M Y') : '' }}</div>
        <div class="fs-5 fw-bold">{{ $fmt($ws['statement_balance']) }}</div>
        @if($ws['latest'] && $ws['statement_balance'] === null)
            <form method="POST" action="{{ url('accounting/phase7/reconcile/imports/' . $ws['latest']->id . '/balance') }}" class="d-flex gap-1 mt-1">@csrf
                <input type="hidden" name="statement_to" value="{{ $ws['as_of'] }}">
                <input type="number" step="0.01" name="closing_balance" class="form-control form-control-sm" placeholder="Enter closing balance" required>
                <button class="btn btn-sm btn-outline-primary">Save</button>
            </form>
        @endif
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2">
        <div class="small text-muted">Balance in the books · {{ \Illuminate\Support\Carbon::parse($ws['as_of'])->format('d M Y') }}</div>
        <div class="fs-5 fw-bold"><a href="{{ url('accounting/phase6/account-transactions?account_id=' . $accountId . '&from=' . substr($ws['as_of'], 0, 4) . '-01-01&to=' . $ws['as_of']) }}">{{ $fmt($ws['gl_balance']) }}</a></div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100 {{ $diff !== null && abs($diff) > 0.009 ? 'border-danger' : ($diff !== null ? 'border-success' : '') }}"><div class="card-body py-2">
        <div class="small text-muted">Difference</div>
        <div class="fs-5 fw-bold {{ $diff !== null && abs($diff) > 0.009 ? 'text-danger' : 'text-success' }}">{{ $fmt($diff) }}</div>
        @if($diff !== null)
            <div class="small {{ abs($diff - $ws['explained']) > 0.009 ? 'text-danger' : 'text-muted' }}">
                Explained by open items: {{ $fmt($ws['explained']) }}{{ abs($diff - $ws['explained']) > 0.009 ? ' — unexplained ' . $fmt($diff - $ws['explained']) : ' ✓' }}
            </div>
        @endif
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body py-2">
        <div class="small text-muted">To reconcile</div>
        <div class="fs-5 fw-bold">{{ $ws['queue']->count() }} <span class="small fw-normal">bank line(s)</span></div>
        <div class="small text-muted">{{ $ws['unpresented']->count() }} book entr{{ $ws['unpresented']->count() === 1 ? 'y' : 'ies' }} not on a statement yet</div>
    </div></div></div>
</div>

{{-- Match panel --}}
@if($selected)
    @php $lineSigned = \App\Services\Accounting\BankReconciliationService::signedLine($selected); @endphp
    <div class="card border-primary mb-3" id="matchPanel"><div class="card-body">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="small text-muted mb-1">Bank statement line</div>
                <div class="border rounded p-2 bg-light">
                    <div class="d-flex justify-content-between"><span>{{ $selected->txn_date?->format('d M Y') }}</span>
                        <strong class="{{ $lineSigned >= 0 ? 'text-success' : 'text-danger' }}">{{ $lineSigned >= 0 ? 'Received' : 'Spent' }} {{ number_format(abs($lineSigned), 2) }}</strong></div>
                    <div class="small">{{ $selected->description }}</div>
                    @if($selected->reference)<div class="small text-muted">Ref {{ $selected->reference }}</div>@endif
                </div>
                <form method="POST" action="{{ url('accounting/phase7/reconcile/lines/' . $selected->id . '/exclude') }}" class="mt-2 d-flex gap-1">@csrf
                    <input name="reason" class="form-control form-control-sm" placeholder="Reason (e.g. duplicate line)">
                    <button class="btn btn-sm btn-outline-secondary text-nowrap">Exclude line</button>
                </form>
                <div class="small text-muted mt-2">Tip: tick several transactions when one deposit pays many invoices. If the bank kept a fee (net settlement), tick "post the difference as a bank fee".</div>
            </div>
            <div class="col-lg-8">
                <form method="GET" class="d-flex gap-2 mb-2">
                    <input type="hidden" name="account_id" value="{{ $accountId }}"><input type="hidden" name="line" value="{{ $selected->id }}">
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Find by customer, supplier, invoice, reference…">
                    <select name="days" class="form-select form-select-sm" style="max-width:140px">
                        @foreach([7, 30, 90] as $d)<option value="{{ $d }}" @selected((int) request('days', 30) === $d)>± {{ $d }} days</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Find</button>
                </form>
                <form method="POST" action="{{ url('accounting/phase7/reconcile/lines/' . $selected->id . '/match') }}" id="matchForm">@csrf
                    <div class="table-responsive" style="max-height:340px;overflow:auto">
                    <table class="table table-sm table-hover align-middle mb-2">
                        <thead class="table-light"><tr><th></th><th>Date</th><th>Transaction</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @forelse($candidates as $c)
                            <tr>
                                <td><input type="checkbox" class="form-check-input cand" name="sources[]" value="{{ $c->type }}:{{ $c->id }}" data-amount="{{ abs($c->amount) }}" @checked($loop->first && abs(abs($c->amount) - abs($lineSigned)) < 0.005)></td>
                                <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($c->date)->format('d M') }}</td>
                                <td class="small">@if($c->url)<a href="{{ $c->url }}" target="_blank">{{ $c->label }}</a>@else{{ $c->label }}@endif</td>
                                <td class="small">{{ $c->reference }}</td>
                                <td class="text-end font-monospace">{{ number_format(abs($c->amount), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small">No unmatched transactions in this window. Record the payment / expense first, or widen the search.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                        <div>Selected <strong id="selTotal">0.00</strong> · bank line <strong>{{ number_format(abs($lineSigned), 2) }}</strong> · difference <strong id="selDiff">—</strong></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="allow_fee" value="1" id="allowFee"><label for="allowFee" class="form-check-label">Post the difference as a bank fee (6050 Bank Charges)</label></div>
                        <button class="btn btn-success btn-sm" id="matchBtn">Match</button>
                    </div>
                </form>

                <div class="border-top mt-3 pt-3 row g-3">
                    <div class="col-md-7">
                        <div class="fw-semibold small mb-1"><i class="bi bi-tag"></i> Not in the books yet? Categorise &amp; post</div>
                        <form method="POST" action="{{ url('accounting/phase7/reconcile/lines/' . $selected->id . '/post') }}" class="d-flex flex-wrap gap-2">@csrf
                            <select name="account_id" class="form-select form-select-sm" style="max-width:260px" required>
                                <option value="">{{ $lineSigned < 0 ? 'Expense / account (e.g. 6050 Bank Charges)' : 'Income / account (e.g. interest)' }}</option>
                                @foreach($postAccounts as $pa)<option value="{{ $pa->id }}" @selected($pa->gl_code === '6050' && $lineSigned < 0)>{{ $pa->gl_code }} — {{ $pa->name }}</option>@endforeach
                            </select>
                            <input name="description" class="form-control form-control-sm" style="max-width:220px" placeholder="Description" value="{{ $selected->description }}">
                            <button class="btn btn-outline-primary btn-sm">Post &amp; match</button>
                        </form>
                    </div>
                    @if($transferTargets->isNotEmpty())
                    <div class="col-md-5">
                        <div class="fw-semibold small mb-1"><i class="bi bi-arrow-left-right"></i> Transfer between our accounts</div>
                        <form method="POST" action="{{ url('accounting/phase7/reconcile/lines/' . $selected->id . '/transfer') }}" class="d-flex gap-2">@csrf
                            <select name="bank_account_id" class="form-select form-select-sm" required>
                                <option value="">{{ $lineSigned < 0 ? 'Money went to…' : 'Money came from…' }}</option>
                                @foreach($transferTargets as $t)<option value="{{ $t->id }}">{{ $t->display_name }}</option>@endforeach
                            </select>
                            <button class="btn btn-outline-primary btn-sm text-nowrap">Record transfer</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div></div>
    <script>
    (function () {
        var line = {{ json_encode(round(abs($lineSigned), 2)) }};
        var boxes = document.querySelectorAll('#matchForm .cand');
        function upd() {
            var t = 0; boxes.forEach(function (b) { if (b.checked) t += parseFloat(b.dataset.amount); });
            t = Math.round(t * 100) / 100; var d = Math.round((t - line) * 100) / 100;
            document.getElementById('selTotal').textContent = t.toFixed(2);
            var el = document.getElementById('selDiff');
            el.textContent = d === 0 ? '0.00 ✓' : (d > 0 ? d.toFixed(2) + ' (fee?)' : d.toFixed(2) + ' short');
            el.className = d === 0 ? 'text-success' : (d > 0 ? 'text-warning' : 'text-danger');
            document.getElementById('allowFee').checked = d > 0;
        }
        boxes.forEach(function (b) { b.addEventListener('change', upd); }); upd();
    })();
    </script>
@endif

{{-- Queue --}}
<div class="card mb-3"><div class="card-header d-flex justify-content-between"><strong>Bank lines to reconcile</strong><span class="small text-muted">{{ $ws['queue']->count() }} line(s) · net {{ number_format((float) $ws['queue']->sum(fn ($l) => \App\Services\Accounting\BankReconciliationService::signedLine($l)), 2) }}</span></div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Description</th><th>Reference</th><th class="text-end">Spent</th><th class="text-end">Received</th><th></th></tr></thead>
    <tbody>
    @forelse($ws['queue'] as $l)
        <tr class="{{ $selected && $selected->id === $l->id ? 'table-primary' : '' }}">
            <td class="small text-nowrap">{{ $l->txn_date?->format('d M Y') }}</td>
            <td class="small">{{ $l->description }}</td>
            <td class="small">{{ $l->reference }}</td>
            <td class="text-end font-monospace">{{ (float) $l->debit > 0 ? number_format((float) $l->debit, 2) : '' }}</td>
            <td class="text-end font-monospace">{{ (float) $l->credit > 0 ? number_format((float) $l->credit, 2) : '' }}</td>
            <td class="text-end"><a href="{{ url('accounting/phase7/reconcile?account_id=' . $accountId . '&line=' . $l->id) }}#matchPanel" class="btn btn-primary btn-xs"><i class="bi bi-link-45deg"></i> Match</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-success small">All imported bank lines are reconciled.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div></div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><strong>In the books, not on a statement yet</strong> <span class="small text-muted">({{ number_format($ws['unpresented_total'], 2) }})</span></div>
        <div class="card-body p-0" style="max-height:320px;overflow:auto">
            <table class="table table-sm mb-0"><tbody>
            @forelse($ws['unpresented']->take(100) as $j)
                @php $src = \App\Services\Accounting\SourceDocumentResolver::resolve($j->source_type, $j->source_id); @endphp
                <tr>
                    <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($j->date)->format('d M') }}</td>
                    <td class="small">@if($src['url'])<a href="{{ $src['url'] }}">{{ $j->name }}</a>@else{{ $j->name }}@endif</td>
                    <td class="text-end font-monospace small {{ $j->signed < 0 ? 'text-danger' : '' }}">{{ number_format($j->signed, 2) }}</td>
                </tr>
            @empty
                <tr><td class="text-muted small">Nothing outstanding.</td></tr>
            @endforelse
            </tbody></table>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><strong>Recently matched</strong></div>
        <div class="card-body p-0" style="max-height:320px;overflow:auto">
            <table class="table table-sm mb-0"><tbody>
            @forelse($ws['recent_matched'] as $l)
                <tr>
                    <td class="small text-nowrap">{{ $l->txn_date?->format('d M') }}</td>
                    <td class="small">{{ $l->description }}
                        <div class="text-muted">
                            @foreach(\App\Services\Accounting\BankReconciliationService::matchesFor($l) as $m)
                                @if($m->label['url'])<a href="{{ $m->label['url'] }}">{{ $m->label['label'] }}</a>@else{{ $m->label['label'] }}@endif{{ $loop->last ? '' : ' · ' }}
                            @endforeach
                            @if($l->fee_amount) · fee {{ number_format((float) $l->fee_amount, 2) }}@endif
                        </div>
                    </td>
                    <td class="text-end font-monospace small">{{ number_format(\App\Services\Accounting\BankReconciliationService::signedLine($l), 2) }}</td>
                    <td><form method="POST" action="{{ url('accounting/phase7/reconcile/lines/' . $l->id . '/unmatch') }}" onsubmit="return confirm('Remove this match?');">@csrf<button class="btn btn-outline-danger btn-xs"><i class="bi bi-x-lg"></i> Unmatch</button></form></td>
                </tr>
            @empty
                <tr><td class="text-muted small">No matches yet.</td></tr>
            @endforelse
            </tbody></table>
        </div></div>
    </div>
</div>
@endif
@endsection
