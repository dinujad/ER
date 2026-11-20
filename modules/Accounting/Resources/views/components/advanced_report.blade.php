{{--
  Advanced financial report shell (LKR).
  Props:
    title, subtitle, from, to, asOf, filterAction, kpis[], chartId, chartConfig (json),
    exportPdf, exportXlsx, insightsUrl, qaUrl, customize (bool)
  Slots: filters, table, aside
--}}
@props([
    'title' => 'Financial Report',
    'subtitle' => null,
    'from' => null,
    'to' => null,
    'asOf' => null,
    'filterAction' => null,
    'kpis' => [],
    'chartId' => null,
    'chartType' => 'doughnut',
    'chartLabels' => [],
    'chartValues' => [],
    'chartColors' => ['#2563eb', '#059669', '#dc2626', '#d97706', '#7c3aed'],
    'exportPdf' => null,
    'exportXlsx' => null,
    'insightsUrl' => null,
    'qaUrl' => null,
    'showCustomize' => true,
    'nonZeroDefault' => true,
])

@php
    $periodLabel = $asOf
        ? ('As of '.$asOf)
        : (($from && $to) ? ($from.' → '.$to) : '');
@endphp

<div class="acct-adv-report" data-adv-report>
    <form method="get" action="{{ $filterAction ?? request()->url() }}" class="acct-adv-toolbar" aria-label="Report filters">
        <div class="acct-adv-toolbar-main">
            @isset($filters)
                {{ $filters }}
            @else
                @if($from !== null)
                    <div>
                        <label class="form-label">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                    </div>
                @endif
                @if($to !== null)
                    <div>
                        <label class="form-label">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                    </div>
                @endif
                @if($asOf !== null)
                    <div>
                        <label class="form-label">As of</label>
                        <input type="date" name="as_of" value="{{ $asOf }}" class="form-control form-control-sm">
                    </div>
                @endif
            @endisset
            <div class="d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-sm btn-primary">Run report</button>
                <a href="{{ $filterAction ?? request()->url() }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
        <div class="acct-adv-toolbar-actions">
            @if($showCustomize)
                <button type="button" class="btn btn-sm btn-outline-secondary" data-adv-customize-toggle>
                    <i class="bi bi-sliders me-1"></i> Customize
                </button>
            @endif
            @if($exportPdf)
                <a href="{{ $exportPdf }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
            @endif
            @if($exportXlsx)
                <a href="{{ $exportXlsx }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            @endif
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
        </div>
    </form>

    @if(count($kpis))
        <div class="acct-kpi-grid">
            @foreach($kpis as $kpi)
                <div class="acct-kpi {{ $kpi['tone'] ?? '' }}">
                    <div class="acct-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="acct-kpi-value">{{ $kpi['value'] }}</div>
                    @if(!empty($kpi['meta']))
                        <div class="acct-kpi-meta">{{ $kpi['meta'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="acct-adv-stage">
        <div class="acct-adv-main">
            <div class="acct-panel mb-0">
                <div class="acct-panel-header">
                    <div>
                        <h2>{{ $title }}</h2>
                        <div class="small text-muted">{{ $subtitle ?? $periodLabel }} · LKR · Accrual (posted journals)</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($insightsUrl)
                            <a href="{{ $insightsUrl }}" class="btn btn-sm btn-light border"><i class="bi bi-stars text-warning me-1"></i>Insights</a>
                        @endif
                        @if($qaUrl)
                            <a href="{{ $qaUrl }}" class="btn btn-sm btn-light border"><i class="bi bi-chat-dots me-1"></i>Q&amp;A</a>
                        @endif
                        <div class="btn-group btn-group-sm" role="group" aria-label="Number format">
                            <button type="button" class="btn btn-outline-secondary active" data-adv-decimals="2">.00</button>
                            <button type="button" class="btn btn-outline-secondary" data-adv-decimals="0">0</button>
                        </div>
                    </div>
                </div>
                <div class="acct-panel-body p-0">
                    @if($chartId && count($chartValues))
                        <div class="p-3 border-bottom">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-5">
                                    <canvas id="{{ $chartId }}" height="180" aria-label="{{ $title }} chart"></canvas>
                                </div>
                                <div class="col-md-7">
                                    <div class="small text-muted mb-2">Visual summary (trusted GL amounts)</div>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($chartLabels as $i => $label)
                                            <li class="d-flex justify-content-between py-1 border-bottom">
                                                <span><span class="acct-swatch" style="background:{{ $chartColors[$i % count($chartColors)] }}"></span>{{ $label }}</span>
                                                <span class="acct-amt" data-adv-money="{{ $chartValues[$i] ?? 0 }}">LKR {{ number_format((float)($chartValues[$i] ?? 0), 2) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="acct-table-wrap border-0 rounded-0" data-adv-table-wrap>
                        {{ $table }}
                    </div>
                </div>
            </div>
        </div>

        @if($showCustomize)
        <aside class="acct-adv-customize" data-adv-customize hidden>
            <div class="acct-adv-customize-head">
                <strong>Customize</strong>
                <button type="button" class="btn btn-sm btn-link" data-adv-customize-toggle aria-label="Close">✕</button>
            </div>
            <div class="acct-adv-customize-body">
                <div class="mb-3">
                    <div class="fw-semibold small mb-2">Display</div>
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" data-adv-nonzero @checked($nonZeroDefault)>
                        <span class="form-check-label">Hide zero-amount rows</span>
                    </label>
                    <label class="form-check mt-1">
                        <input type="checkbox" class="form-check-input" data-adv-neg-red checked>
                        <span class="form-check-label">Show negatives in red</span>
                    </label>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold small mb-2">Expand / collapse</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-1" data-adv-expand-all>Expand all groups</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-adv-collapse-all>Collapse all groups</button>
                </div>
                <div class="small text-muted">
                    Accounting method is accrual based on posted journals. Cash/accrual toggle is not enabled (LKR single-ledger).
                </div>
                @isset($aside)
                    <hr>
                    {{ $aside }}
                @endisset
            </div>
        </aside>
        @endif
    </div>
</div>

@once
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  function money(n, decimals) {
    var v = Number(n) || 0;
    return 'LKR ' + v.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
  }
  document.querySelectorAll('[data-adv-report]').forEach(function (root) {
    var decimals = 2;
    var customize = root.querySelector('[data-adv-customize]');
    root.querySelectorAll('[data-adv-customize-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!customize) return;
        customize.hidden = !customize.hidden;
      });
    });
    function applyMoney() {
      root.querySelectorAll('[data-adv-money]').forEach(function (el) {
        el.textContent = money(el.getAttribute('data-adv-money'), decimals);
      });
    }
    root.querySelectorAll('[data-adv-decimals]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        root.querySelectorAll('[data-adv-decimals]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        decimals = parseInt(btn.getAttribute('data-adv-decimals'), 10) || 0;
        applyMoney();
      });
    });
    var nonzero = root.querySelector('[data-adv-nonzero]');
    if (nonzero) {
      nonzero.addEventListener('change', function () {
        root.querySelectorAll('tr[data-amount]').forEach(function (tr) {
          var amt = Math.abs(parseFloat(tr.getAttribute('data-amount')) || 0);
          tr.style.display = (nonzero.checked && amt < 0.005) ? 'none' : '';
        });
      });
      nonzero.dispatchEvent(new Event('change'));
    }
    var negRed = root.querySelector('[data-adv-neg-red]');
    if (negRed) {
      negRed.addEventListener('change', function () {
        root.classList.toggle('acct-adv-neg-red', negRed.checked);
      });
      root.classList.add('acct-adv-neg-red');
    }
    root.querySelectorAll('[data-adv-group-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-adv-group-toggle');
        root.querySelectorAll('tr[data-group="' + id + '"]').forEach(function (tr) {
          tr.classList.toggle('d-none');
        });
        btn.classList.toggle('collapsed');
      });
    });
    var expand = root.querySelector('[data-adv-expand-all]');
    var collapse = root.querySelector('[data-adv-collapse-all]');
    if (expand) expand.addEventListener('click', function () {
      root.querySelectorAll('tr[data-group]').forEach(function (tr) { tr.classList.remove('d-none'); });
      root.querySelectorAll('[data-adv-group-toggle]').forEach(function (b) { b.classList.remove('collapsed'); });
    });
    if (collapse) collapse.addEventListener('click', function () {
      root.querySelectorAll('tr[data-group]').forEach(function (tr) { tr.classList.add('d-none'); });
      root.querySelectorAll('[data-adv-group-toggle]').forEach(function (b) { b.classList.add('collapsed'); });
    });
  });
})();
</script>
@endpush
@endonce

@if($chartId && count($chartValues))
@push('scripts')
<script>
(function () {
  var el = document.getElementById(@json($chartId));
  if (!el || typeof Chart === 'undefined') return;
  var type = @json($chartType);
  var opts = { plugins: { legend: { display: type !== 'doughnut', position: 'bottom' } }, responsive: true };
  if (type === 'doughnut') opts.cutout = '62%';
  if (type === 'bar') opts.scales = { y: { beginAtZero: true } };
  new Chart(el, {
    type: type,
    data: {
      labels: @json($chartLabels),
      datasets: [{
        data: @json(array_map('floatval', $chartValues)),
        backgroundColor: @json(array_values($chartColors)),
        borderWidth: 0
      }]
    },
    options: opts
  });
})();
</script>
@endpush
@endif
