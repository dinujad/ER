<div class="txn-filters-wrap mb-0">
    <div class="d-flex align-items-center justify-content-between py-1 mb-2 border-bottom border-light" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#collapseFilter" aria-expanded="true" aria-controls="collapseFilter">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            @if (!empty($icon))
                {!! $icon !!}
            @else
                <i class="bi bi-funnel text-primary"></i>
            @endif
            <span>{{ $title ?? __('accounting::transactions.filters') }}</span>
        </h6>
        <i class="bi bi-chevron-down small text-muted"></i>
    </div>
    <div id="collapseFilter" class="collapse show">
        <div class="pt-1 pb-0">
            {{ $slot }}
        </div>
    </div>
</div>
