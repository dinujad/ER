{{-- expects: $journal (Collection from DocumentJournalService) --}}
@forelse($journal as $txn)
    <div class="border rounded mb-2 {{ $txn['reversed'] ? 'opacity-50' : '' }}">
        <div class="d-flex flex-wrap justify-content-between gap-2 px-2 py-1 bg-light small">
            <div>
                <a href="{{ url('accounting/phase6/journal/' . urlencode($txn['transaction_number'])) }}" class="fw-semibold">{{ $txn['transaction_number'] }}</a>
                · {{ $txn['date'] }} · {{ $txn['name'] }}
                @if($txn['reversed'])<span class="badge bg-secondary">Reversed</span>@endif
            </div>
            <div class="text-muted">{{ str_replace('_', ' ', (string) $txn['type']) }}
                @if($txn['source']['url']) · <a href="{{ $txn['source']['url'] }}">{{ $txn['source']['label'] }}</a>@else · {{ $txn['source']['label'] }}@endif
            </div>
        </div>
        <table class="table table-sm mb-0">
            <thead><tr class="small text-muted"><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @foreach($txn['lines'] as $line)
                <tr>
                    <td style="width:55%">
                        <a href="{{ url('accounting/phase6/account-transactions?account_id=' . $line['account_id'] . '&from=' . substr((string) $txn['date'], 0, 4) . '-01-01&to=' . now()->toDateString()) }}">{{ $line['gl_code'] }} {{ $line['account'] }}</a>
                    </td>
                    <td class="text-end font-monospace">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                    <td class="text-end font-monospace">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="text-muted small">No journal entries posted for this document yet.</div>
@endforelse
