{{-- Shared permission checkboxes partial. Used in create & edit. --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Permissions</label>
    @if(isset($role) && $role->isAdmin())
        <div class="alert alert-info py-2">
            <i class="bi bi-shield-fill-check me-1"></i>
            The Admin role always has all permissions and cannot be restricted.
        </div>
    @else
        <div class="alert alert-info py-2 small">
            Edit and Delete are administrator-only unless you select the corresponding permission below.
            View and Create permissions do not grant Edit or Delete access. Only administrators can change role permissions.
        </div>
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAll(true)">Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Deselect All</button>
        </div>

        @foreach($permissionGroups as $groupKey => $group)
        <div class="card mb-2 border-0 bg-light">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <input type="checkbox" class="form-check-input group-toggle" id="grp_{{ $groupKey }}"
                           data-group="{{ $groupKey }}"
                           onchange="toggleGroup('{{ $groupKey }}', this.checked)">
                    <label for="grp_{{ $groupKey }}" class="form-check-label fw-semibold text-primary mb-0" style="cursor:pointer;">
                        {{ $group['label'] }}
                    </label>
                </div>
                <div class="ps-4 d-flex flex-wrap gap-3">
                    @foreach($group['permissions'] as $key => $label)
                    <div class="form-check mb-0">
                        <input class="form-check-input perm-check perm-group-{{ $groupKey }}" type="checkbox"
                               name="permissions[]"
                               value="{{ $key }}"
                               id="perm_{{ str_replace('.', '_', $key) }}"
                               {{ in_array($key, $selectedPerms ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="perm_{{ str_replace('.', '_', $key) }}">
                            {{ $label }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>

<script>
function toggleAll(state) {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = state);
    document.querySelectorAll('.group-toggle').forEach(cb => cb.checked = state);
}
function toggleGroup(group, state) {
    document.querySelectorAll('.perm-group-' + group).forEach(cb => cb.checked = state);
}
// Keep group checkbox in sync with individual perm checkboxes
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.perm-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const group = [...this.classList].find(c => c.startsWith('perm-group-'))?.replace('perm-group-', '');
            if (!group) return;
            const all = document.querySelectorAll('.perm-group-' + group);
            const checked = [...all].filter(c => c.checked).length;
            const groupToggle = document.getElementById('grp_' + group);
            if (groupToggle) {
                groupToggle.checked = checked === all.length;
                groupToggle.indeterminate = checked > 0 && checked < all.length;
            }
        });
    });

    // Init group toggles
    document.querySelectorAll('.group-toggle').forEach(function(gt) {
        const group = gt.dataset.group;
        const all = document.querySelectorAll('.perm-group-' + group);
        const checked = [...all].filter(c => c.checked).length;
        gt.checked = checked === all.length && all.length > 0;
        gt.indeterminate = checked > 0 && checked < all.length;
    });
});
</script>
