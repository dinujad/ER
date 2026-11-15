<div class="modal fade" id="previousReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="previous-return-form" class="modal-content" onsubmit="return confirm('Add this previous return credit?');">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">පරණ return එකතු කරන්න</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2" id="previous-return-business"></p>
                <div class="mb-2">
                    <label class="form-label small" for="previous_return_amount">Amount we owe (Rs)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="previous_return_amount" class="form-control" required>
                </div>
                <div>
                    <label class="form-label small" for="previous_return_note">Note (optional)</label>
                    <input type="text" name="note" id="previous_return_note" class="form-control" maxlength="500" placeholder="Old sale / return reference">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('previousReturnModal')?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const form = document.getElementById('previous-return-form');
        if (!button || !form) return;
        form.action = button.getAttribute('data-action') || '';
        const label = document.getElementById('previous-return-business');
        if (label) label.textContent = button.getAttribute('data-business') || '';
        const amount = document.getElementById('previous_return_amount');
        if (amount) amount.value = '';
    });
</script>
