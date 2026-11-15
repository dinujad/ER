{{-- Customer search for sales person order form --}}
@php
    $inputId = $inputId ?? ('sp_cs_input_' . uniqid());
    $hiddenId = $hiddenId ?? 'customer_id';
    $selected = $selectedCustomer ?? null;
@endphp
<style>
    .sp-customer-search .sp-cs-results {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 280px;
        overflow-y: auto;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        background: #fff;
        border: 1px solid #e2e8f0;
    }
    .sp-customer-search .sp-cs-result {
        cursor: pointer;
        border: none;
        border-bottom: 1px solid #eef2f7;
        padding: 0.7rem 1rem;
        text-align: left;
        width: 100%;
        background: #fff;
    }
    .sp-customer-search .sp-cs-result:hover,
    .sp-customer-search .sp-cs-result:focus {
        background: #f0f7ff;
    }
    .sp-customer-search .sp-cs-result:last-child { border-bottom: none; }
    .sp-customer-search .sp-cs-name { font-weight: 700; color: #1e3a5f; }
    .sp-customer-search .sp-cs-meta { font-size: 0.78rem; color: #64748b; }
    .sp-customer-search .sp-cs-loading {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    .sp-customer-search.is-invalid .sp-cs-input { border-color: #dc3545; }
</style>

<div class="sp-customer-search @error($hiddenId) is-invalid @enderror"
     data-search-url="{{ route('sales-person.customers.search') }}"
     data-initial-id="{{ $selected?->id ?? old($hiddenId, '') }}"
     data-initial-name="{{ $selected?->name ?? '' }}"
     data-initial-business="{{ $selected?->business_name ?? '' }}"
     data-initial-display="{{ $selected?->display_name ?? '' }}"
     data-initial-phone="{{ $selected?->phone ?? '' }}"
     @php
        $initialClaim = $selected ? app(\App\Services\SaleReturnClaimService::class)->breakdown($selected) : ['previous' => 0, 'system' => 0, 'full' => 0];
     @endphp
     data-initial-claim-previous="{{ $initialClaim['previous'] }}"
     data-initial-claim-system="{{ $initialClaim['system'] }}"
     data-initial-claim-full="{{ $initialClaim['full'] }}">
    <label for="{{ $inputId }}" class="form-label fw-semibold">
        <i class="bi bi-person-search me-1"></i> Search Customer
    </label>
    <div class="d-flex gap-2 flex-wrap align-items-start">
        <div class="position-relative flex-grow-1" style="min-width: 200px;">
            <input type="text"
                   id="{{ $inputId }}"
                   class="form-control sp-cs-input @error($hiddenId) is-invalid @enderror"
                   placeholder="Business name, owner name, phone, or email..."
                   autocomplete="off"
                   value="{{ $selected ? $selected->display_name . ($selected->phone ? ' (' . $selected->phone . ')' : '') : '' }}">
            <div class="sp-cs-loading spinner-border spinner-border-sm text-primary" role="status"></div>
            <div class="sp-cs-results"></div>
        </div>
        <a href="{{ route('sales-person.customers.create') }}" class="btn btn-outline-primary text-nowrap">
            <i class="bi bi-person-plus me-1"></i>New Customer
        </a>
    </div>
    <input type="hidden" name="{{ $hiddenId }}" id="{{ $hiddenId }}" class="sp-cs-hidden" value="{{ old($hiddenId, $selected?->id ?? '') }}" required>
    <div class="sp-cs-selected text-muted mt-2 small"></div>
    <div class="sp-cs-claim alert alert-info py-2 px-3 small mt-2 mb-0 d-none"></div>
  @error($hiddenId)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div class="form-text">Search by business name, owner name, or phone. The selected customer shows the business name.</div>
</div>

<script>
(function () {
    if (window.__spCustomerSearchInit) return;
    window.__spCustomerSearchInit = true;

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function initContainer(container) {
        if (container.__spInitialized) return;
        container.__spInitialized = true;

        const input = container.querySelector('.sp-cs-input');
        const hidden = container.querySelector('.sp-cs-hidden');
        const results = container.querySelector('.sp-cs-results');
        const selected = container.querySelector('.sp-cs-selected');
        const claimBox = container.querySelector('.sp-cs-claim');
        const loading = container.querySelector('.sp-cs-loading');
        const searchUrl = container.dataset.searchUrl;
        let debounceTimer = null;
        let lastItems = [];

        function hideResults() { results.style.display = 'none'; }
        function showResults() { results.style.display = 'block'; }

        function businessLabel(item) {
            const business = (item.business_name || '').trim();
            const display = (item.display_name || '').trim();
            const owner = (item.name || '').trim();
            return business || display || owner;
        }

        function ownerLine(item, label) {
            const owner = (item.name || '').trim();
            if (!owner || owner.toLowerCase() === label.toLowerCase()) return '';
            return ' · Owner: ' + escapeHtml(owner);
        }

        function money(value) {
            const n = parseFloat(value || '0');
            return (isNaN(n) ? 0 : n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function showClaim(item) {
            if (!claimBox) return;
            const full = parseFloat(item.claim_full || '0');
            if (!(full > 0)) {
                claimBox.classList.add('d-none');
                claimBox.innerHTML = '';
                return;
            }
            claimBox.classList.remove('d-none');
            claimBox.innerHTML = 'මේ business එකට claim කරන්න පුළුවන්: <strong>Rs ' + money(full) + '</strong>'
                + '<div class="text-muted">පරණ return Rs ' + money(item.claim_previous) + ' · system return Rs ' + money(item.claim_system)
                + '. එක order එකකට invoice එකේ 30% දක්වා claim කරන්න පුළුවන්. Payment දානකොට tick කරලා ගාන දාන්න.</div>';
        }

        function selectItem(item) {
            hideResults();
            const label = businessLabel(item);
            hidden.value = item.id;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            input.value = label + (item.phone ? ' (' + item.phone + ')' : '');
            selected.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Selected: <strong>' + escapeHtml(label) + '</strong>' +
                ownerLine(item, label) +
                (item.phone ? ' · ' + escapeHtml(item.phone) : '') + '</span>';
            showClaim(item);
            container.classList.remove('is-invalid');
        }

        function renderResults(items) {
            lastItems = items;
            results.innerHTML = '';
            if (!items.length) {
                results.innerHTML = '<div class="px-3 py-3 text-muted small">No customers found. <a href="' + @json(route('sales-person.customers.create')) + '">Add new customer</a></div>';
                showResults();
                return;
            }
            items.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sp-cs-result';
                const label = businessLabel(item);
                btn.innerHTML = '<div class="sp-cs-name">' + escapeHtml(label) + '</div>' +
                    '<div class="sp-cs-meta">' +
                    (item.phone ? '<i class="bi bi-telephone me-1"></i>' + escapeHtml(item.phone) : '') +
                    ownerLine(item, label) +
                    (item.email ? ' · ' + escapeHtml(item.email) : '') +
                    '</div>';
                btn.addEventListener('click', () => selectItem(item));
                results.appendChild(btn);
            });
            showResults();
        }

        function fetchResults(q) {
            loading.style.display = 'block';
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.ok ? r.json() : [])
                .then(data => renderResults(Array.isArray(data) ? data : []))
                .catch(() => {
                    results.innerHTML = '<div class="px-3 py-3 text-danger small">Search failed. Try again.</div>';
                    showResults();
                })
                .finally(() => { loading.style.display = 'none'; });
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = input.value.trim();
            if (!q) {
                hidden.value = '';
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
                selected.innerHTML = '';
                if (claimBox) {
                    claimBox.classList.add('d-none');
                    claimBox.innerHTML = '';
                }
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
            fetch(searchUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(items => {
                    if (items.length) selectItem(items[0]);
                    else alert('No customer found for: ' + q);
                });
        });

        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) hideResults();
        });

        const initId = container.dataset.initialId;
        if (initId && hidden.value) {
            const label = businessLabel({
                business_name: container.dataset.initialBusiness || '',
                display_name: container.dataset.initialDisplay || '',
                name: container.dataset.initialName || '',
            });
            const phone = container.dataset.initialPhone || '';
            if (label) {
                selected.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Selected: <strong>' + escapeHtml(label) + '</strong>' +
                    ownerLine({ name: container.dataset.initialName || '' }, label) +
                    (phone ? ' · ' + escapeHtml(phone) : '') + '</span>';
            }
            showClaim({
                claim_full: container.dataset.initialClaimFull,
                claim_previous: container.dataset.initialClaimPrevious,
                claim_system: container.dataset.initialClaimSystem,
            });
        }
    }

    document.querySelectorAll('.sp-customer-search').forEach(initContainer);
})();
</script>
