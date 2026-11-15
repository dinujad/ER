@php
    $statusAccent = [
        'pending' => '#f59e0b',
        'processing' => '#0ea5e9',
        'afterchecking' => '#3b82f6',
        'completed' => '#22c55e',
        'cancelled' => '#ef4444',
    ];
    $issuedCols = ($workflow ?? '') === 'issued';
    $colspanEmpty = $issuedCols ? 13 : 10;

    $defaultTab = request('status');
    if (!$defaultTab || !isset($boardStatuses[$defaultTab])) {
        $defaultTab = collect($boardStatuses)->keys()->first(
            fn ($key) => ($statusCounts[$key] ?? 0) > 0
        ) ?? array_key_first($boardStatuses);
    }
@endphp

<div id="orders-status-board" class="orders-status-board" data-default-tab="{{ $defaultTab }}">
    <div class="px-3 pt-3 pb-1">
        <div class="status-nav-pills d-flex flex-wrap gap-2" role="tablist">
            @foreach($boardStatuses as $statusKey => $statusMeta)
                <button
                    type="button"
                    role="tab"
                    class="btn btn-outline-{{ $statusMeta['badge'] }} status-tab-btn {{ $statusKey === $defaultTab ? 'active' : '' }}"
                    data-status-tab="{{ $statusKey }}"
                    aria-selected="{{ $statusKey === $defaultTab ? 'true' : 'false' }}"
                    aria-controls="status-panel-{{ $statusKey }}"
                >
                    {{ $statusMeta['label'] }}
                    <span class="badge bg-{{ $statusMeta['badge'] }} ms-1">{{ $statusCounts[$statusKey] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
        <p class="text-muted small mb-0 mt-2">Status button click කළ විට එම table එක පමණක් පෙන්වයි. Move to click කළාම order එක අදාළ status එකට යයි.</p>
    </div>

    @foreach($boardStatuses as $statusKey => $statusMeta)
        @php $statusOrders = $ordersByStatus->get($statusKey, collect()); @endphp
        <section
            id="status-panel-{{ $statusKey }}"
            class="status-board-section {{ $statusKey === $defaultTab ? 'is-active' : '' }}"
            data-status-panel="{{ $statusKey }}"
            role="tabpanel"
            style="border-top-color: {{ $statusAccent[$statusKey] ?? '#94a3b8' }};"
        >
            <div class="status-board-head">
                <h4>
                    <span class="s-badge s-badge-{{ $statusMeta['badge'] }} me-2">{{ $statusMeta['label'] }}</span>
                    {{ $statusCounts[$statusKey] ?? 0 }} order(s)
                </h4>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Sales Person</th>
                            <th>Check</th>
                            @if($issuedCols)
                                <th>Business</th>
                                <th>Bags</th>
                                <th>Bundles</th>
                            @endif
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid / Due</th>
                            <th class="text-end">Actions</th>
                            <th style="min-width: 200px;">Move to</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statusOrders as $order)
                            @include('erp.orders.partials.board-row', [
                                'order' => $order,
                                'workflow' => $workflow,
                                'manufacturingNeededOrderIds' => $manufacturingNeededOrderIds,
                                'boardStatuses' => $boardStatuses,
                            ])
                        @empty
                            <tr>
                                <td colspan="{{ $colspanEmpty }}" class="text-center text-muted py-4 small">No orders in this status.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    @if($orders->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
            <h5>No Orders Found</h5>
            <p class="small">Filters වලට ගැලපෙන orders නැහැ.</p>
            <a href="{{ route('orders.create') }}" class="btn btn-outline-primary btn-sm">Create Order</a>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var board = document.getElementById('orders-status-board');
    if (!board) {
        return;
    }

    var buttons = board.querySelectorAll('.status-tab-btn');
    var panels = board.querySelectorAll('[data-status-panel]');
    function showTab(statusKey) {
        buttons.forEach(function (btn) {
            var active = btn.dataset.statusTab === statusKey;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.statusPanel === statusKey);
        });
        if (history.replaceState) {
            history.replaceState(null, '', '#status-' + statusKey);
        } else {
            location.hash = 'status-' + statusKey;
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            showTab(btn.dataset.statusTab);
        });
    });

    var initial = (location.hash || '').replace('#status-', '');
    if (!initial || !board.querySelector('[data-status-panel="' + initial + '"]')) {
        initial = board.dataset.defaultTab;
    }
    showTab(initial);
});
</script>
