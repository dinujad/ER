@extends('accounting::layouts.app')
@section('title', 'Add Account')

@section('css')
<style>
/* ── Page chrome ──────────────────────────────────────── */
.coa-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
    border-radius: 14px;
    padding: 22px 28px 18px;
    margin-bottom: 24px;
    color: #fff;
}
.coa-hero h4 { font-weight: 700; font-size: 1.25rem; margin: 0; }
.coa-hero p  { opacity: .75; font-size: .875rem; margin: 4px 0 0; }

/* ── Section cards ────────────────────────────────────── */
.form-section {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.form-section-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
}
.section-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.section-icon.blue  { background: #eff6ff; color: #1e40af; }
.section-icon.green { background: #f0fdf4; color: #15803d; }
.section-icon.amber { background: #fffbeb; color: #b45309; }
.section-icon.violet{ background: #f5f3ff; color: #6d28d9; }
.form-section-title { font-weight: 600; font-size: .95rem; color: #1e293b; margin: 0; }
.form-section-body  { padding: 20px 20px 10px; }

/* ── Type radio cards ─────────────────────────────────── */
.type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
}
.type-card input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
.type-card label {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 6px; padding: 14px 10px; border-radius: 10px;
    border: 2px solid #e9ecef; background: #f8fafc;
    cursor: pointer; transition: all .18s; font-size: .8rem; font-weight: 500;
    text-align: center; line-height: 1.2; min-height: 76px;
}
.type-card label i { font-size: 1.35rem; }
.type-card input:checked + label { border-width: 2px; box-shadow: 0 0 0 3px rgba(0,0,0,.06); font-weight: 600; }

.type-asset   label { color: #1d4ed8; } .type-asset   input:checked + label { background: #eff6ff; border-color: #3b82f6; }
.type-income  label { color: #15803d; } .type-income  input:checked + label { background: #f0fdf4; border-color: #22c55e; }
.type-expense label { color: #b91c1c; } .type-expense input:checked + label { background: #fef2f2; border-color: #ef4444; }
.type-liability label { color: #b45309; } .type-liability input:checked + label { background: #fffbeb; border-color: #f59e0b; }
.type-equity  label { color: #6d28d9; } .type-equity  input:checked + label { background: #f5f3ff; border-color: #8b5cf6; }

/* ── Form controls ────────────────────────────────────── */
.form-label { font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .03em; }
.form-control, .form-select {
    border-radius: 8px; border: 1.5px solid #e2e8f0;
    padding: 9px 12px; font-size: .92rem; color: #1e293b;
    transition: border-color .18s, box-shadow .18s;
}
.form-control:focus, .form-select:focus {
    border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); outline: none;
}
.input-group .form-control { border-radius: 0 8px 8px 0; }
.input-group-text { border-radius: 8px 0 0 8px; border: 1.5px solid #e2e8f0; border-right: 0; background: #f8fafc; color: #64748b; font-size:.9rem; }
.select2-container--default .select2-selection--single {
    height: 42px; border-radius: 8px; border: 1.5px solid #e2e8f0;
    display: flex; align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px; color: #1e293b; padding-left: 12px; font-size: .92rem; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
.select2-container--default.select2-container--focus .select2-selection--single { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.select2-container { width: 100% !important; }

/* ── Opening balance panel ────────────────────────────── */
#openingBalancePanel {
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1.5px dashed #86efac;
    border-radius: 12px;
    padding: 18px 20px;
    margin-top: 6px;
}

/* ── Detail type hint ─────────────────────────────────── */
#detailTypeHint {
    background: #eff6ff; border-left: 3px solid #3b82f6;
    border-radius: 0 8px 8px 0; padding: 10px 14px;
    font-size: .85rem; color: #1e40af; display: none;
}

/* ── Toggle switch ────────────────────────────────────── */
.form-switch .form-check-input { width: 2.5em; height: 1.4em; cursor: pointer; }
.form-switch .form-check-label { font-size: .92rem; color: #374151; cursor: pointer; }

/* ── Action bar ───────────────────────────────────────── */
.action-bar {
    background: #fff; border: 1px solid #e9ecef;
    border-radius: 12px; padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.btn-save {
    background: linear-gradient(135deg, #1e3a5f, #0d2137);
    color: #fff; border: none; border-radius: 9px;
    padding: 10px 28px; font-weight: 600; font-size: .95rem;
    transition: all .18s;
}
.btn-save:hover { background: linear-gradient(135deg, #2563eb, #1e3a5f); color: #fff; transform: translateY(-1px); }
.btn-cancel-link { color: #64748b; font-size: .9rem; text-decoration: none; }
.btn-cancel-link:hover { color: #1e3a5f; }

/* ── Invalid feedback ─────────────────────────────────── */
.invalid-feedback { font-size: .8rem; }
.is-invalid ~ .invalid-feedback { display: block; }
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="coa-hero">
    <div class="d-flex align-items-center gap-3">
        <div style="background:rgba(255,255,255,.15);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
            <i class="bi bi-plus-circle"></i>
        </div>
        <div>
            <h4>Add Account</h4>
            <p>Create a new Chart of Accounts entry</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ url('accounting/chart_of_account/store') }}" id="coaForm" novalidate>
    @csrf
    <input type="hidden" name="date" value="{{ date('Y-m-d') }}">

    {{-- ① Account Classification ─────────────────────── --}}
    <div class="form-section">
        <div class="form-section-head">
            <div class="section-icon blue"><i class="bi bi-diagram-3"></i></div>
            <div>
                <p class="form-section-title">Account Classification</p>
                <small class="text-muted" style="font-size:.78rem;">Select type, subtype and detail category</small>
            </div>
        </div>
        <div class="form-section-body">

            {{-- Account Type --}}
            <div class="mb-3">
                <label class="form-label">Account Type <span class="text-danger">*</span></label>
                <div class="type-grid">
                    @php $types = [
                        'asset'     => ['bi-bank',          'Asset',     'type-asset'],
                        'income'    => ['bi-graph-up-arrow','Income',    'type-income'],
                        'expense'   => ['bi-receipt',       'Expense',   'type-expense'],
                        'liability' => ['bi-exclamation-circle','Liability','type-liability'],
                        'equity'    => ['bi-pie-chart',     'Equity',    'type-equity'],
                    ]; @endphp
                    @foreach($types as $val => [$icon, $label, $cls])
                    <div class="type-card {{ $cls }}">
                        <input type="radio" name="account_type" id="type_{{ $val }}" value="{{ $val }}"
                            {{ old('account_type') == $val ? 'checked' : '' }}>
                        <label for="type_{{ $val }}">
                            <i class="bi {{ $icon }}"></i>
                            {{ $label }}
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('account_type')<div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
                {{-- Account Subtype --}}
                <div class="col-md-6">
                    <label class="form-label">Account Subtype <span class="text-danger">*</span></label>
                    <select name="account_subtype_id" id="account_subtype_id"
                        class="form-select @error('account_subtype_id') is-invalid @enderror" required>
                        <option value="">— Select account type first —</option>
                        @foreach($account_subtypes as $sub)
                        <option value="{{ $sub->id }}"
                            data-account-type="{{ $sub->account_type }}"
                            {{ old('account_subtype_id') == $sub->id ? 'selected' : '' }}>
                            {{ $sub->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('account_subtype_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Account Detail Type --}}
                <div class="col-md-6">
                    <label class="form-label">Account Detail Type <span class="text-danger">*</span></label>
                    <select name="detail_type_id" id="detail_type_id"
                        class="form-select @error('detail_type_id') is-invalid @enderror" required>
                        <option value="">— Select subtype first —</option>
                        @foreach($account_detail_types as $dt)
                        <option value="{{ $dt->id }}"
                            data-subtype-id="{{ $dt->account_subtype_id }}"
                            data-description="{{ $dt->description }}"
                            {{ old('detail_type_id') == $dt->id ? 'selected' : '' }}>
                            {{ $dt->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('detail_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="detailTypeHint"></div>
                </div>
            </div>

        </div>
    </div>

    {{-- ② Account Details ────────────────────────────── --}}
    <div class="form-section">
        <div class="form-section-head">
            <div class="section-icon green"><i class="bi bi-card-text"></i></div>
            <div>
                <p class="form-section-title">Account Details</p>
                <small class="text-muted" style="font-size:.78rem;">Name, GL code, parent and currency</small>
            </div>
        </div>
        <div class="form-section-body">
            <div class="row g-3">

                {{-- Name --}}
                <div class="col-md-6">
                    <label class="form-label" for="name">Account Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required placeholder="e.g. Cash at Hand">
                    </div>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- GL Code --}}
                <div class="col-md-6">
                    <label class="form-label" for="gl_code">GL Code <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" name="gl_code" id="gl_code"
                            class="form-control @error('gl_code') is-invalid @enderror"
                            value="{{ old('gl_code') }}" required maxlength="50" placeholder="e.g. 1001">
                    </div>
                    @error('gl_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Parent Account --}}
                <div class="col-md-6">
                    <label class="form-label" for="parent_id">Parent Account</label>
                    <select name="parent_id" id="parent_id" class="form-select select2-searchable">
                        <option value="">None (Top-level)</option>
                        @foreach($chart_of_accounts as $acct)
                        <option value="{{ $acct->id }}"
                            data-subtype-id="{{ $acct->account_subtype_id }}"
                            {{ old('parent_id') == $acct->id ? 'selected' : '' }}>
                            [{{ $acct->gl_code }}] {{ $acct->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Currency --}}
                <div class="col-md-6">
                    <label class="form-label" for="currency_id">Currency <span class="text-danger">*</span></label>
                    <select name="currency_id" id="currency_id"
                        class="form-select select2-searchable @error('currency_id') is-invalid @enderror" required>
                        <option value="">— Select currency —</option>
                        @foreach($currencies as $cur)
                        <option value="{{ $cur->id }}" {{ old('currency_id') == $cur->id ? 'selected' : '' }}>
                            {{ $cur->currency }} ({{ $cur->code }})
                        </option>
                        @endforeach
                    </select>
                    @error('currency_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ③ Settings ──────────────────────────────────── --}}
    <div class="form-section">
        <div class="form-section-head">
            <div class="section-icon amber"><i class="bi bi-sliders2"></i></div>
            <div>
                <p class="form-section-title">Settings</p>
                <small class="text-muted" style="font-size:.78rem;">Status, permissions and notes</small>
            </div>
        </div>
        <div class="form-section-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="d-flex gap-3 align-items-center mt-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="active_toggle"
                                onchange="document.getElementById('active_val').value = this.checked ? 1 : 0"
                                {{ old('active', 1) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="active_toggle">Active</label>
                        </div>
                        <input type="hidden" name="active" id="active_val" value="{{ old('active', 1) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manual Entries Allowed</label>
                    <div class="d-flex gap-3 align-items-center mt-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_manual_toggle"
                                onchange="document.getElementById('allow_manual_val').value = this.checked ? 1 : 0"
                                {{ old('allow_manual', 1) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_manual_toggle">Yes</label>
                        </div>
                        <input type="hidden" name="allow_manual" id="allow_manual_val" value="{{ old('allow_manual', 1) }}">
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                    class="form-control @error('notes') is-invalid @enderror"
                    placeholder="Optional description for this account">{{ old('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ④ Opening Balance ─────────────────────────────── --}}
    <div class="form-section">
        <div class="form-section-head">
            <div class="section-icon violet"><i class="bi bi-wallet2"></i></div>
            <div>
                <p class="form-section-title">Opening Balance</p>
                <small class="text-muted" style="font-size:.78rem;">Optional initial balance for this account</small>
            </div>
            <div class="ms-auto">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="has_opening_balance_chk"
                        {{ old('has_opening_balance') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="has_opening_balance_chk" style="font-size:.88rem;">Set opening balance</label>
                </div>
                <input type="hidden" name="has_opening_balance" id="has_opening_balance_val" value="{{ old('has_opening_balance') ? 1 : 0 }}">
            </div>
        </div>
        <div class="form-section-body" id="openingBalanceSection" style="{{ old('has_opening_balance') ? '' : 'display:none' }}">
            <div id="openingBalancePanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="opening_balance">Opening Balance Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#dcfce7;border-color:#86efac;color:#15803d;font-weight:600;">LKR</span>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance"
                                class="form-control @error('opening_balance') is-invalid @enderror"
                                value="{{ old('opening_balance') }}" placeholder="0.00"
                                style="border-color:#86efac;">
                        </div>
                        @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payment_type_id">Payment Method</label>
                        <select name="payment_type_id" id="payment_type_id" class="form-select"
                            style="border-color:#86efac;">
                            @foreach($payment_types as $pt)
                            <option value="{{ $pt->id }}" {{ old('payment_type_id') == $pt->id ? 'selected' : '' }}>
                                {{ $pt->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action bar --}}
    <div class="action-bar">
        <a href="{{ url('accounting/chart_of_account') }}" class="btn-cancel-link">
            <i class="bi bi-arrow-left me-1"></i> Back to list
        </a>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted" style="font-size:.82rem;"><span class="text-danger">*</span> required fields</span>
            <button type="submit" class="btn-save">
                <i class="bi bi-check2-circle me-1"></i> Save Account
            </button>
        </div>
    </div>

</form>
@endsection

@section('javascript')
<script>
(function () {
    /* ── Data from server ───────────────────────────── */
    var subtypes     = @json($account_subtypes);
    var detailTypes  = @json($account_detail_types);
    var chartAccts   = @json($chart_of_accounts);

    var selectedType    = "{{ old('account_type') }}";
    var selectedSubtype = "{{ old('account_subtype_id') }}";
    var selectedDetail  = "{{ old('detail_type_id') }}";
    var selectedParent  = "{{ old('parent_id') }}";

    /* ── DOM refs ───────────────────────────────────── */
    var subtypeEl   = document.getElementById('account_subtype_id');
    var detailEl    = document.getElementById('detail_type_id');
    var parentEl    = document.getElementById('parent_id');
    var hintEl      = document.getElementById('detailTypeHint');

    /* ── Account type radio change ──────────────────── */
    document.querySelectorAll('input[name=account_type]').forEach(function (r) {
        r.addEventListener('change', function () {
            filterSubtypes(this.value);
            filterParents(null); // clear parent when type changes
        });
    });

    /* ── Subtype change → filter detail + parent ────── */
    subtypeEl.addEventListener('change', function () {
        filterDetailTypes(this.value);
        filterParents(this.value);
    });

    /* ── Detail type change → show hint ─────────────── */
    detailEl.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        var desc = opt ? opt.getAttribute('data-description') : '';
        if (desc) {
            hintEl.style.display = 'block';
            hintEl.textContent = desc;
        } else {
            hintEl.style.display = 'none';
        }
    });

    /* ── Opening balance toggle ─────────────────────── */
    var obChk   = document.getElementById('has_opening_balance_chk');
    var obVal   = document.getElementById('has_opening_balance_val');
    var obSec   = document.getElementById('openingBalanceSection');
    var obInput = document.getElementById('opening_balance');

    obChk.addEventListener('change', function () {
        obVal.value = this.checked ? 1 : 0;
        obSec.style.display = this.checked ? '' : 'none';
        if (!this.checked) obInput.removeAttribute('required');
        else obInput.setAttribute('required', '');
    });

    /* ── Helpers ────────────────────────────────────── */
    function filterSubtypes(accountType) {
        var prev = subtypeEl.value;
        subtypeEl.innerHTML = '<option value="">— Select subtype —</option>';
        subtypes.forEach(function (s) {
            if (!accountType || s.account_type === accountType) {
                var o = document.createElement('option');
                o.value = s.id;
                o.setAttribute('data-account-type', s.account_type);
                o.textContent = s.name;
                if (String(s.id) === String(prev) || String(s.id) === String(selectedSubtype)) o.selected = true;
                subtypeEl.appendChild(o);
            }
        });
        filterDetailTypes(subtypeEl.value);
    }

    function filterDetailTypes(subtypeId) {
        var prev = detailEl.value;
        detailEl.innerHTML = '<option value="">— Select detail type —</option>';
        hintEl.style.display = 'none';
        detailTypes.forEach(function (d) {
            if (!subtypeId || String(d.account_subtype_id) === String(subtypeId)) {
                var o = document.createElement('option');
                o.value = d.id;
                o.setAttribute('data-subtype-id', d.account_subtype_id);
                o.setAttribute('data-description', d.description || '');
                o.textContent = d.name;
                if (String(d.id) === String(prev) || String(d.id) === String(selectedDetail)) o.selected = true;
                detailEl.appendChild(o);
            }
        });
        // Re-trigger hint
        detailEl.dispatchEvent(new Event('change'));
    }

    function filterParents(subtypeId) {
        var prev = parentEl.value;
        parentEl.innerHTML = '<option value="">None (Top-level)</option>';
        chartAccts.forEach(function (a) {
            if (!subtypeId || String(a.account_subtype_id) === String(subtypeId)) {
                var o = document.createElement('option');
                o.value = a.id;
                o.textContent = '[' + (a.gl_code || '-') + '] ' + a.name;
                if (String(a.id) === String(prev) || String(a.id) === String(selectedParent)) o.selected = true;
                parentEl.appendChild(o);
            }
        });
        if ($.fn.select2) $(parentEl).trigger('change.select2');
    }

    /* ── Init on page load ──────────────────────────── */
    if (selectedType) {
        filterSubtypes(selectedType);
        if (selectedSubtype) {
            subtypeEl.value = selectedSubtype;
            filterDetailTypes(selectedSubtype);
            filterParents(selectedSubtype);
            if (selectedDetail) detailEl.value = selectedDetail;
            if (selectedParent) parentEl.value = selectedParent;
        }
    }

    /* ── Select2 for searchable dropdowns ───────────── */
    $(document).ready(function () {
        $('.select2-searchable').select2({ placeholder: '— Search —', allowClear: true, width: '100%' });
        $('#account_subtype_id').select2({ placeholder: '— Select subtype —', width: '100%' });
        $('#detail_type_id').select2({ placeholder: '— Select detail type —', width: '100%' });

        /* keep in sync with native events after Select2 */
        $('#account_subtype_id').on('change.select2', function () {
            filterDetailTypes($(this).val());
            filterParents($(this).val());
        });
        $('#detail_type_id').on('change.select2', function () {
            var desc = $(this).find(':selected').data('description');
            hintEl.style.display = desc ? 'block' : 'none';
            if (desc) hintEl.textContent = desc;
        });
    });

    /* ── Form validation highlight ──────────────────── */
    document.getElementById('coaForm').addEventListener('submit', function (e) {
        var valid = true;
        if (!document.querySelector('input[name=account_type]:checked')) {
            document.querySelector('.type-grid').style.outline = '2px solid #ef4444';
            document.querySelector('.type-grid').style.borderRadius = '8px';
            valid = false;
        } else {
            document.querySelector('.type-grid').style.outline = '';
        }
        if (!valid) e.preventDefault();
    });
})();
</script>
@endsection
