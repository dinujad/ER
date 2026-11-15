@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Sales Person</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Details</h6>
                </div>
                <div class="card-body">
                    @canRoute('sales-persons.update')
<form method="POST" action="{{ route('sales-persons.update', $salesPerson->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $salesPerson->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email (login)</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $salesPerson->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone (SMS) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $salesPerson->phone ?? '') }}" placeholder="07XXXXXXXX" required>
                            <small class="text-muted">Used to send login credentials via SMS.</small>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="bank_account_number" class="form-label">Bank account (optional)</label>
                            <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $salesPerson->bank_account_number) }}">
                            @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="sales_route_id" class="form-label">Sales Route</label>
                            <select name="sales_route_id" id="sales_route_id" class="form-control @error('sales_route_id') is-invalid @enderror">
                                <option value="">— No route —</option>
                                @foreach($salesRoutes as $route)
                                    <option value="{{ $route->id }}" @selected((string) old('sales_route_id', $salesPerson->sales_route_id) === (string) $route->id)>
                                        {{ $route->name }}@if($route->code) ({{ $route->code }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Peers on this route can see each other's customers.</small>
                            @error('sales_route_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Change password</h6>
                        <p class="small text-muted mb-3">Leave blank to keep the current password.</p>

                        <div class="mb-3">
                            <label for="password" class="form-label">New password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm new password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="send_password_sms" id="send_password_sms" value="1" {{ old('send_password_sms', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="send_password_sms">
                                Send new password via SMS when saving
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Sales Person</button>
                        <a href="{{ route('sales-persons.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
@endcanRoute
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned customers & orders</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <span class="badge bg-info text-dark">{{ $counts['customers'] }} customer(s)</span>
                        <span class="badge bg-secondary">{{ $counts['orders'] }} order(s)</span>
                    </p>
                    @if($counts['customers'] > 0)
                        <a href="{{ route('erp.customers.index', ['tab' => 'wholesale', 'added_by' => $salesPerson->id]) }}" class="btn btn-sm btn-outline-primary mb-3">
                            View wholesale customers
                        </a>
                    @endif

                    @if($counts['customers'] > 0 || $counts['orders'] > 0)
                        @if($otherSalesPersons->isEmpty())
                            <div class="alert alert-warning small mb-0">
                                This sales person has assigned data. Add another sales person before deleting or reassigning.
                            </div>
                        @else
                            @canRoute('sales-persons.reassign')
<form method="POST" action="{{ route('sales-persons.reassign', $salesPerson->id) }}" id="transfer-customers-form">
                                @csrf
                                @if($assignedCustomers->isNotEmpty())
                                    <label for="transfer_customer_ids" class="form-label">Customers to transfer</label>
                                    <select name="customer_ids[]" id="transfer_customer_ids" class="form-select @error('customer_ids') is-invalid @enderror" multiple data-placeholder="Search and select customers…">
                                        @foreach($assignedCustomers as $customer)
                                            @php
                                                $label = filled($customer->business_name) ? $customer->business_name : $customer->name;
                                                if (filled($customer->business_name) && filled($customer->name) && strcasecmp($customer->business_name, $customer->name) !== 0) {
                                                    $label .= ' — ' . $customer->name;
                                                }
                                                if ($customer->phone) {
                                                    $label .= ' (' . $customer->phone . ')';
                                                }
                                                $label .= ' · ' . $customer->sales_orders_count . ' order(s)';
                                            @endphp
                                            <option value="{{ $customer->id }}" @selected(collect(old('customer_ids', []))->contains((string) $customer->id) || collect(old('customer_ids', []))->contains($customer->id))>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('customer_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    <div class="d-flex flex-wrap gap-2 mt-2 mb-3">
                                        <button type="button" class="btn btn-link btn-sm p-0" id="select-all-customers">Select all</button>
                                        <span class="text-muted">·</span>
                                        <button type="button" class="btn btn-link btn-sm p-0" id="clear-customers">Clear</button>
                                    </div>
                                @endif

                                <label for="reassign_to_edit" class="form-label">Transfer to sales person</label>
                                <select name="reassign_to" id="reassign_to_edit" class="form-select @error('reassign_to') is-invalid @enderror" required>
                                    <option value="">Select sales person…</option>
                                    @foreach($otherSalesPersons as $sp)
                                        <option value="{{ $sp->id }}" {{ (string) old('reassign_to') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                                    @endforeach
                                </select>
                                @error('reassign_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                                @if($orphanOrderCount > 0)
                                    <div class="form-check mt-2 mb-2">
                                        <input class="form-check-input" type="checkbox" name="move_orphan_orders" id="move_orphan_orders" value="1" {{ old('move_orphan_orders') ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="move_orphan_orders">
                                            Also move {{ $orphanOrderCount }} unlinked order(s) (no customer attached)
                                        </label>
                                    </div>
                                @endif

                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    @if($assignedCustomers->isNotEmpty())
                                        <button type="submit" class="btn btn-outline-primary btn-sm" data-transfer-mode="selected">
                                            <i class="bi bi-arrow-left-right me-1"></i> Move selected customers & orders
                                        </button>
                                    @endif
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" name="move_all" value="1" data-transfer-mode="all">
                                        <i class="bi bi-people me-1"></i> Move all customers & orders
                                    </button>
                                </div>
                            </form>
@endcanRoute

                        @endif
                    @else
                        <p class="text-muted small mb-0">No customers or orders assigned to this sales person.</p>
                    @endif
                </div>
            </div>

            <div class="card shadow border-warning mb-4">
                <div class="card-body">
                    <h6 class="fw-bold"><i class="bi bi-chat-dots text-primary me-1"></i> Resend password</h6>
                    <p class="small text-muted mb-3">Generate a random new password and send it by SMS.</p>
                    @canRoute('sales-persons.resend')
<form action="{{ route('sales-persons.resend', $salesPerson->id) }}" method="POST" onsubmit="return confirm('Generate a new password and send via SMS?');">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-chat-dots me-1"></i> Resend password (SMS)
                        </button>
                    </form>
@endcanRoute

                </div>
            </div>

            <div class="card shadow border-danger">
                <div class="card-body">
                    <h6 class="fw-bold text-danger"><i class="bi bi-trash me-1"></i> Delete sales person</h6>
                    @if($counts['customers'] > 0 || $counts['orders'] > 0)
                        <p class="small text-muted mb-3">
                            This person has {{ $counts['customers'] }} customer(s) and {{ $counts['orders'] }} order(s).
                            You must reassign them before delete (orders would otherwise be lost).
                        </p>
                        @if($otherSalesPersons->isEmpty())
                            <button type="button" class="btn btn-danger btn-sm" disabled>Cannot delete — add another sales person first</button>
                        @else
                            @canRoute('sales-persons.destroy')
<form method="POST" action="{{ route('sales-persons.destroy', $salesPerson->id) }}" onsubmit="return confirm('Delete this sales person? Customers and orders will move to the selected person.');">
                                @csrf
                                @method('DELETE')
                                <label for="reassign_to_delete" class="form-label small">Reassign customers & orders to</label>
                                <select name="reassign_to" id="reassign_to_delete" class="form-select form-select-sm mb-2" required>
                                    <option value="">Select…</option>
                                    @foreach($otherSalesPersons as $sp)
                                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-danger btn-sm">Delete sales person</button>
                            </form>
@endcanRoute
                        @endif
                    @else
                        <p class="small text-muted mb-3">No customers or orders linked. Safe to delete.</p>
                        @canRoute('sales-persons.destroy')
<form method="POST" action="{{ route('sales-persons.destroy', $salesPerson->id) }}" onsubmit="return confirm('Delete this sales person permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete sales person</button>
                        </form>
@endcanRoute
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(($counts['customers'] > 0 || $counts['orders'] > 0) && $otherSalesPersons->isNotEmpty() && $assignedCustomers->isNotEmpty())
<script>
    jQuery(function ($) {
        var $form = $('#transfer-customers-form');
        var $customerSelect = $('#transfer_customer_ids');

        if ($.fn.select2 && $customerSelect.length) {
            $customerSelect.select2({
                placeholder: $customerSelect.data('placeholder') || 'Search and select customers…',
                allowClear: true,
                width: '100%',
                closeOnSelect: false,
                dropdownParent: $('body'),
            });
        }

        $('#select-all-customers').on('click', function () {
            var allValues = $customerSelect.find('option').map(function () {
                return this.value;
            }).get();
            $customerSelect.val(allValues).trigger('change');
        });

        $('#clear-customers').on('click', function () {
            $customerSelect.val(null).trigger('change');
        });

        $form.on('submit', function (event) {
            var mode = $(document.activeElement).data('transfer-mode') || 'selected';
            var targetName = $('#reassign_to_edit option:selected').text().trim();

            if (!$('#reassign_to_edit').val()) {
                return true;
            }

            if (mode === 'all') {
                if (!confirm('Move all customers and orders to ' + targetName + '?')) {
                    event.preventDefault();
                }
                return;
            }

            var selected = $customerSelect.val() || [];
            if (selected.length === 0) {
                alert('Select at least one customer to transfer.');
                event.preventDefault();
                return;
            }

            if (!confirm('Move ' + selected.length + ' selected customer(s) and their orders to ' + targetName + '?')) {
                event.preventDefault();
            }
        });
    });
</script>
@endif
@endsection
