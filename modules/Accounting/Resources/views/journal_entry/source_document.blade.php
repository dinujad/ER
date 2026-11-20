<section class="acct-card mb-3" aria-label="Source document">
    <div class="acct-card-head">
        <h6><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i> Source document</h6>
        @if($source['url'])
            <a class="btn btn-sm btn-outline-primary" href="{{ $source['url'] }}">
                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                {{ in_array($source['type'], ['field_expense', 'vehicle_expense'], true) ? 'Open receipt' : 'Open source record' }}
            </a>
        @endif
    </div>
    <div class="acct-card-body">
        <div class="fw-bold">{{ $source['label'] ?: 'Source document unavailable' }}</div>
        @if(filled($description))
            <div class="mt-1">{{ $description }}</div>
        @endif
        @if(filled($reference))
            <div class="text-muted small mt-1">Reference {{ $reference }}</div>
        @endif
        @php $details = array_filter($source['details'] ?? [], fn ($value) => filled($value)); @endphp
        @if($details)
            <dl class="row g-2 mt-2 mb-0">
                @foreach($details as $label => $value)
                    <dt class="col-sm-3 text-muted">{{ $label }}</dt>
                    <dd class="col-sm-9 text-break mb-0">{{ $value }}</dd>
                @endforeach
            </dl>
        @endif
        @unless($source['url'])
            <div class="text-muted small mt-2">No separate document is available for this entry.</div>
        @endunless
    </div>
</section>
