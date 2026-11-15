<div class="modal fade" id="createAccountSubtypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18)">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;border-radius:14px 14px 0 0;padding:20px 24px">
                <h5 class="modal-title fw-bold" style="font-size:1.05rem">
                    <i class="bi bi-layers me-2"></i>Add Account Subtype
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ url('accounting/settings/account_subtypes/store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="acct-label">Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" id="account_type_input" class="acct-control" required>
                            <option value="">— Select type —</option>
                            @foreach($account_types as $at)
                            <option value="{{ $at->id }}" {{ old('account_type') == $at->id ? 'selected' : '' }}>
                                {{ ucfirst($at->plural_name) }}
                            </option>
                            @endforeach
                        </select>
                        @error('account_type')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="acct-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="acct-control" value="{{ old('name') }}" required placeholder="Subtype name">
                        @error('name')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="acct-label">Description</label>
                        <textarea name="description" class="acct-control" rows="2" placeholder="Optional description">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" id="subtype_active" value="1"
                            {{ old('active', 1) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="subtype_active" style="font-size:.88rem">Active</label>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc;border-radius:0 0 14px 14px;padding:14px 20px">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-acct-save" style="padding:9px 22px;font-size:.88rem">
                        <i class="bi bi-check2-circle me-1"></i> Save Subtype
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
