@php
    $selectedIds = collect($selectedIds ?? [])->map(fn ($id) => (string) $id);
    $currentRouteId = $currentRouteId ?? null;
@endphp
<div class="mb-3" id="assign-sales-persons">
    <label class="form-label">Assign sales persons <span class="text-muted fw-normal">(optional)</span></label>
    <input type="search" class="form-control form-control-sm mb-2" id="assign-sp-search" placeholder="Search name or phone…" autocomplete="off">
    <div class="border rounded p-2" style="max-height:240px; overflow:auto;">
        @forelse($salesPersons as $sp)
            @php
                $searchText = strtolower(trim($sp->name . ' ' . ($sp->phone ?? '')));
            @endphp
            <div class="form-check assign-sp-row" data-search="{{ $searchText }}">
                <input class="form-check-input" type="checkbox" name="sales_person_ids[]" value="{{ $sp->id }}" id="sp_{{ $sp->id }}"
                    @checked($selectedIds->contains((string) $sp->id))>
                <label class="form-check-label" for="sp_{{ $sp->id }}">
                    {{ $sp->name }}
                    @if($sp->phone)
                        <small class="text-muted">{{ $sp->phone }}</small>
                    @endif
                    @if($sp->sales_route_id && (string) $sp->sales_route_id !== (string) $currentRouteId)
                        <small class="text-muted">(currently on another route)</small>
                    @endif
                </label>
            </div>
        @empty
            <div class="text-muted small">No sales persons yet. Add them first.</div>
        @endforelse
        <div class="text-muted small py-2 d-none" id="assign-sp-empty">No matching sales person.</div>
    </div>
    <small class="text-muted">Search and tick to assign. You can save the route with nobody assigned.</small>
</div>
@once
@push('scripts')
<script>
(function () {
    var box = document.getElementById('assign-sales-persons');
    if (!box) return;
    var input = document.getElementById('assign-sp-search');
    var empty = document.getElementById('assign-sp-empty');
    var rows = box.querySelectorAll('.assign-sp-row');
    input.addEventListener('input', function () {
        var q = (input.value || '').toLowerCase().trim();
        var shown = 0;
        rows.forEach(function (row) {
            var match = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
            row.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        if (empty) empty.classList.toggle('d-none', shown > 0 || rows.length === 0);
    });
})();
</script>
@endpush
@endonce
