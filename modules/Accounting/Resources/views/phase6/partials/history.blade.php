{{-- Phase 6.1 — document history timeline. expects: $logs (collection of App\Models\AuditLog) --}}
<div class="acct-history">
    @forelse($logs as $log)
        <div class="border-start border-3 ps-3 pb-3 mb-1 position-relative {{ in_array($log->event, ['deleted', 'invoice_voided'], true) ? 'border-danger' : (str_contains($log->event, 'issued') || $log->event === 'created' ? 'border-success' : 'border-primary') }}">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <strong>{{ $log->eventLabel() }}</strong>
                    <span class="text-muted">· {{ $log->document_label }}</span>
                </div>
                <div class="small text-muted text-nowrap">
                    <i class="bi bi-person"></i> {{ $log->user_name ?: 'System' }}
                    · <i class="bi bi-clock"></i> {{ $log->created_at?->format('Y-m-d H:i:s') }}
                </div>
            </div>
            @if($log->note)
                <div class="small mt-1"><i class="bi bi-chat-left-text"></i> {{ $log->note }}</div>
            @endif
            @php $changes = $log->changes(); @endphp
            @if(count($changes) > 0)
                <details class="small mt-1" @if($log->event !== 'created' && $log->event !== 'deleted' && count($changes) <= 6) open @endif>
                    <summary class="text-primary" style="cursor:pointer;">{{ count($changes) }} field{{ count($changes) === 1 ? '' : 's' }}</summary>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead><tr class="text-muted"><th style="width:30%">Field</th><th>Before</th><th>After</th></tr></thead>
                            <tbody>
                            @foreach($changes as $c)
                                <tr>
                                    <td>{{ $c['field'] }}</td>
                                    <td class="text-danger text-break">{{ $c['old'] }}</td>
                                    <td class="text-success text-break">{{ $c['new'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    @empty
        <div class="text-muted small">No history recorded yet. Changes made after the audit trail was installed appear here.</div>
    @endforelse
</div>
