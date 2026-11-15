{{-- Advanced product search: Design No (SKU) or product name --}}
@php
    $mode = $mode ?? 'order-form';
    $inputId = $inputId ?? ('sp_ps_input_' . uniqid());
@endphp
<style>
    .sp-product-search .sp-ps-results {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 360px;
        overflow-y: auto;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }
    .sp-product-search .sp-ps-result {
        cursor: pointer;
        border: none;
        border-bottom: 1px solid #eef2f7;
        padding: 0.75rem 1rem;
        text-align: left;
        width: 100%;
        background: #fff;
    }
    .sp-product-search .sp-ps-result:hover,
    .sp-product-search .sp-ps-result:focus {
        background: #f0f7ff;
    }
    .sp-product-search .sp-ps-result:last-child { border-bottom: none; }
    .sp-product-search .sp-ps-sku { font-weight: 700; color: #1e3a5f; }
    .sp-product-search .sp-ps-meta { font-size: 0.78rem; color: #64748b; }
    .sp-product-search .sp-ps-selected {
        min-height: 1.5rem;
        font-size: 0.875rem;
    }
    .sp-product-search .sp-ps-loading {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
</style>

<div class="sp-product-search"
     data-mode="{{ $mode }}"
     data-search-url="{{ route('sales-person.products.search') }}"
     data-order-create-url="{{ route('sales-person.orders.create') }}">
    <label for="{{ $inputId }}" class="form-label fw-semibold">
        <i class="bi bi-search me-1"></i> Search Design No (SKU) or Product Name
    </label>
    <div class="position-relative">
        <input type="text"
               id="{{ $inputId }}"
               class="form-control form-control-lg sp-ps-input"
               placeholder="Type design number, SKU, size, or product name..."
               autocomplete="off"
               value="{{ $initialQuery ?? '' }}">
        <div class="sp-ps-loading spinner-border spinner-border-sm text-primary" role="status"></div>
        <div class="sp-ps-results list-group"></div>
    </div>
    <div class="sp-ps-selected text-muted mt-2"></div>
    @if($mode === 'order-form')
        <div class="form-text">Result එකක් click කළාම sizes/variations load වෙනවා. Enter දාලා පළමු match select කරන්න පුළුවන්.</div>
    @else
        <div class="form-text">Product එකක් select කළාම New Order page එක open වෙනවා.</div>
    @endif
</div>

<script>
(function () {
    if (window.__spProductSearchInit) return;
    window.__spProductSearchInit = true;

    function formatRs(value) {
        const n = Number(value || 0);
        return n > 0 ? 'Rs ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
    }

    function priceLabel(item) {
        if (item.price_from <= 0) return 'Price not set';
        if (item.price_from === item.price_to) return formatRs(item.price_from);
        return formatRs(item.price_from) + ' – ' + formatRs(item.price_to);
    }

    function initContainer(container) {
        if (container.__spInitialized) return;
        container.__spInitialized = true;
        const input = container.querySelector('.sp-ps-input');
        const results = container.querySelector('.sp-ps-results');
        const selected = container.querySelector('.sp-ps-selected');
        const loading = container.querySelector('.sp-ps-loading');
        const mode = container.dataset.mode;
        const searchUrl = container.dataset.searchUrl;
        const orderCreateUrl = container.dataset.orderCreateUrl;
        let debounceTimer = null;
        let lastItems = [];

        function hideResults() {
            results.style.display = 'none';
        }

        function showResults() {
            results.style.display = 'block';
        }

        function renderResults(items) {
            lastItems = items;
            results.innerHTML = '';

            if (!items.length) {
                results.innerHTML = '<div class="list-group-item text-muted small py-3">No products found. Try design number or name.</div>';
                showResults();
                return;
            }

            items.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sp-ps-result';
                const typeBadge = item.product_type === 'variable'
                    ? `<span class="badge bg-primary-subtle text-primary border ms-1">${item.variation_count} sizes</span>`
                    : '<span class="badge bg-secondary-subtle text-secondary border ms-1">Single</span>';
                btn.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="sp-ps-sku">${item.sku || '—'}</div>
                            <div class="text-dark">${item.name}</div>
                            <div class="sp-ps-meta">${priceLabel(item)} · Stock: ${Number(item.stock_total || 0).toLocaleString()}</div>
                        </div>
                        <div class="text-end">${typeBadge}</div>
                    </div>
                `;
                btn.addEventListener('click', () => selectItem(item));
                results.appendChild(btn);
            });

            showResults();
        }

        function selectItem(item) {
            hideResults();
            input.value = item.sku || item.name;
            selected.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Selected: <strong>${item.sku}</strong> — ${item.name}</span>`;

            if (mode === 'dashboard') {
                const url = new URL(orderCreateUrl, window.location.origin);
                url.searchParams.set('product_id', item.id);
                if (item.sku) url.searchParams.set('q', item.sku);
                window.location.href = url.toString();
                return;
            }

            if (typeof window.spOnProductSelected === 'function') {
                window.spOnProductSelected(item);
            }
        }

        function fetchResults(q) {
            loading.style.display = 'block';
            fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => renderResults(Array.isArray(data) ? data : []))
                .catch(() => {
                    results.innerHTML = '<div class="list-group-item text-danger small py-3">Search failed. Try again.</div>';
                    showResults();
                })
                .finally(() => { loading.style.display = 'none'; });
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = input.value.trim();
            selected.innerHTML = '';
            if (q.length < 1) {
                hideResults();
                return;
            }
            debounceTimer = setTimeout(() => fetchResults(q), 280);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const q = input.value.trim();
            if (!q) return;
            if (lastItems.length) {
                selectItem(lastItems[0]);
                return;
            }
            fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(items => {
                    if (items.length) selectItem(items[0]);
                    else alert('No product found for: ' + q);
                });
        });

        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) hideResults();
        });

        container.__spSelectById = function (productId, label) {
            if (label) {
                input.value = label;
                selected.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Loading product...</span>`;
            }
            fetch(`${searchUrl}?q=${encodeURIComponent(label || productId)}`)
                .then(r => r.json())
                .then(items => {
                    const match = items.find(i => String(i.id) === String(productId)) || items[0];
                    if (match) {
                        input.value = match.sku || match.name;
                        selected.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Selected: <strong>${match.sku}</strong> — ${match.name}</span>`;
                        if (typeof window.spOnProductSelected === 'function') {
                            window.spOnProductSelected(match);
                        }
                    }
                });
        };
    }

    document.querySelectorAll('.sp-product-search').forEach(initContainer);
})();
</script>
