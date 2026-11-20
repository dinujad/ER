@extends('erp.layout')

@section('title', 'Order Details')

@php
    $chequeMin = now()->format('Y-m-d');
    $chequeMaxNormal = now()->addDays(45)->format('Y-m-d');
    $chequeMaxException = now()->addDays(180)->format('Y-m-d');
    $chequeMinException = now()->subDays(30)->format('Y-m-d'); 
@endphp

@section('content')
<style>
    .order-check-checkbox {
        width: 18px;
        height: 18px;
        border: 2px solid #0d6efd;
        cursor: pointer;
        accent-color: #0d6efd;
    }
    /* Form wraps header/body/footer — keep Bootstrap scrollable flex working */
    #orderCheckModal .modal-dialog,
    #issueInvoiceModal .modal-dialog,
    #editInvoiceModal .modal-dialog {
        max-height: calc(100vh - 1rem);
        margin: 0.5rem auto;
    }
    #orderCheckModal .modal-content,
    #orderCheckModal .modal-content > form,
    #issueInvoiceModal .modal-content,
    #issueInvoiceModal .modal-content > form,
    #editInvoiceModal .modal-content,
    #editInvoiceModal .modal-content > form {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 1rem);
        overflow: hidden;
    }
    #orderCheckModal .modal-body,
    #issueInvoiceModal .modal-body,
    #editInvoiceModal .modal-body {
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        flex: 1 1 auto;
        min-height: 0;
    }
    #orderCheckModal .modal-header,
    #orderCheckModal .modal-footer,
    #issueInvoiceModal .modal-header,
    #issueInvoiceModal .modal-footer,
    #editInvoiceModal .modal-header,
    #editInvoiceModal .modal-footer {
        flex-shrink: 0;
    }
</style>
<div class="row">
    <!-- Order Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order #{{ $order->order_number }}
                    @if($order->is_urgent)
                        <span class="badge bg-danger ms-1">Urgent</span>
                    @endif
                </h5>
                @if($order->canAdminEditItems() && auth()->user()?->can_access('orders.edit'))
                    @canRoute('orders.edit')
<a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square me-1"></i> Edit Order
                    </a>
@endcanRoute
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Customer:</strong> {{ optional($order->customer)->display_name }} <br>
                        @if($order->customer?->business_name && $order->customer?->name && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                            <span class="text-muted small">Contact: {{ $order->customer->name }}</span><br>
                        @endif
                        <strong>Sales Person:</strong>
                        @if($order->salesPerson && $order->salesPerson->role === 'sales_person')
                            {{ $order->salesPerson->name }}
                            @if(filled($order->salesPerson->phone))
                                <span class="text-muted">({{ $order->salesPerson->phone }})</span>
                            @endif
                        @else
                            —
                        @endif
                        <br>
                        <strong>Date:</strong> {{ $order->order_date->format('Y-m-d') }} <br>
                        @if(filled($order->notes))
                            <strong>Note:</strong> <span style="white-space: pre-wrap;">{{ $order->notes }}</span><br>
                        @endif
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'cancelled' ? 'danger' : 'warning') }} mb-2">
                            {{ ucfirst($order->status) }}
                        </span>
                        
                        @canRoute('orders.update_status')
<form action="{{ route('orders.update_status', $order->id) }}" method="POST" class="d-inline-block ms-2">
                            @csrf
                            <div class="input-group input-group-sm">
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="afterchecking" {{ $order->status == 'afterchecking' ? 'selected' : '' }}>After Checking</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    @if($order->status === 'completed')
                                        <option value="completed" selected>Completed</option>
                                    @endif
                                </select>
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Update</button>
                            </div>
                        </form>
@endcanRoute
                        @if($order->status !== 'completed')
                            <div class="small text-muted mt-1">Completed කරන්න Order Check popup එකෙන් Invoice Issue කරන්න.</div>
                        @endif
                        @error('invoice_issue')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 text-end">
                        @if(in_array($order->status, ['pending', 'processing'], true))
                            @php $headerCheck = $order->orderCheckProgress(); @endphp
                            <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#orderCheckModal">
                                Order Check
                                @if($headerCheck['checked'] > 0)
                                    <span class="badge bg-success ms-1">{{ $headerCheck['checked'] }}/{{ $headerCheck['total'] }}</span>
                                @endif
                            </button>
                        @endif
                        <a href="{{ route('orders.order-form', $order->id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-text me-1"></i> Download Order Form
                        </a>
                        @if($order->status === 'afterchecking')
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#issueInvoiceModal">
                                Issue Invoice
                            </button>
                        @endif
                        @if($order->status === 'completed')
                            @canRoute('orders.update_invoice')
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editInvoiceModal">
                                Edit Invoice
                            </button>
                            @endcanRoute
                            <a href="{{ route('orders.invoice', $order->id) }}" class="btn btn-primary btn-sm">
                                Download Invoice
                            </a>
                            @if(auth()->user()?->isSystemAdmin() || auth()->user()?->can_access('accounting.view'))
                            <a href="{{ url('accounting/phase6/invoices/' . $order->id) }}" class="btn btn-outline-dark btn-sm" title="Lines, tax, payments, journal entries and history">
                                <i class="bi bi-journal-text"></i> Accounting view
                            </a>
                            @endif
                        @endif
                        @if($order->canAcceptSaleReturn() && $order->items->contains(fn ($i) => $i->returnableQuantity() > 0))
                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#saleReturnModal">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Sale Return
                            </button>
                        @endif
                        @canRoute('orders.destroy')
<form method="POST" action="{{ route('orders.destroy', $order->id) }}" class="d-inline"
                            onsubmit="return confirm(@js('Delete order ' . $order->order_number . '? This cannot be undone.' . ($order->status === 'completed' ? ' Stock from this invoice will be restored.' : '')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i> Delete Order
                            </button>
                        </form>
@endcanRoute
                    </div>
                </div>

                <h6>Items</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Design No</th>
                            <th>Product</th>
                            <th>Variation</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Stock / නිෂ්පාදන තත්ත්වය</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        @php
                            $insight = $stockInsights[$item->id] ?? ['available' => 0, 'ordered' => (float) $item->quantity, 'required' => 0];
                        @endphp
                        <tr>
                            <td><strong>{{ $item->product?->sku ?: '—' }}</strong></td>
                            <td>{{ $item->product?->name ?? 'Product' }}</td>
                            <td>{{ $item->variation?->variation_name ?: '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->total_price, 2) }}</td>
                            <td>
                                @if($order->status === 'completed')
                                    <div class="text-success small fw-semibold">
                                        මෙම order එක complete කරලා
                                        @if($order->salesPerson)
                                            <strong>{{ $order->salesPerson->name }}</strong> මගින් customer වෙත යවා ඇත.
                                        @else
                                            sales person මගින් customer වෙත යවා ඇත.
                                        @endif
                                    </div>
                                @elseif($insight['required'] > 0)
                                    <div class="text-danger small fw-semibold">
                                        @if($insight['available'] <= 0)
                                            <span class="badge bg-danger mb-1">Stock නැත</span><br>
                                            Order qty {{ number_format($insight['ordered'], 0) }} — {{ ucfirst($channel ?? 'wholesale') }} stock 0.
                                            නිෂ්පාදනය කරන්න ඕන.
                                        @else
                                            <span class="badge bg-warning text-dark mb-1">Stock අඩුයි</span><br>
                                            Available {{ number_format($insight['available'], 0) }} / Order {{ number_format($insight['ordered'], 0) }}.
                                            තව {{ number_format($insight['required'], 0) }}ක් නිෂ්පාදනය කරන්න ඕන.
                                        @endif
                                    </div>
                                @else
                                    <div class="text-success small fw-semibold">
                                        <span class="badge bg-success mb-1">Stock OK</span><br>
                                        Available {{ number_format($insight['available'], 0) }} — order qty {{ number_format($insight['ordered'], 0) }} cover වෙනවා.
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $invoiceSubtotal = (float) ($order->subtotal ?? $order->items->sum('total_price'));
                        @endphp
                        @if($order->status === 'completed')
                        <tr>
                            <th colspan="6" class="text-end">Subtotal:</th>
                            <th>{{ number_format($invoiceSubtotal, 2) }}</th>
                        </tr>
                        @if(($order->discount_amount ?? 0) > 0)
                        <tr>
                            <th colspan="6" class="text-end">Discount:</th>
                            <th class="text-danger">-{{ number_format($order->discount_amount, 2) }}</th>
                        </tr>
                        @endif
                        @if(($order->shipping ?? 0) > 0)
                        <tr>
                            <th colspan="6" class="text-end">Transport:</th>
                            <th>{{ number_format($order->shipping, 2) }}</th>
                        </tr>
                        @endif
                        @if(($order->invoice_other_expenses ?? 0) > 0)
                        <tr>
                            <th colspan="6" class="text-end">Other expenses:</th>
                            <th>{{ number_format($order->invoice_other_expenses, 2) }}</th>
                        </tr>
                        @endif
                        @endif
                        <tr>
                            <th colspan="6" class="text-end">Net Total:</th>
                            <th>{{ number_format($order->total_amount, 2) }}</th>
                        </tr>
                        @if($order->chargesInvoiceTax())
                        <tr>
                            <th colspan="6" class="text-end">SSCL:</th>
                            <th>{{ number_format((float) $order->invoice_sscl_amount, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">VAT:</th>
                            <th>{{ number_format((float) $order->invoice_vat_amount, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">Invoice Total (incl. SSCL &amp; VAT):</th>
                            <th>{{ number_format($order->invoice_total, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="6" class="text-end">Issued Bags Count:</th>
                            <th>{{ $order->issued_bags_count ?? '—' }}</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">Issued Bundle Count:</th>
                            <th>{{ $order->issued_bundle_count ?? '—' }}</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">Paid Amount:</th>
                            <th class="text-success">{{ number_format($order->paid_amount, 2) }}</th>
                        </tr>
                        @if($order->total_returned > 0)
                        <tr>
                            <th colspan="6" class="text-end text-warning">Sale returns:</th>
                            <th class="text-warning">-{{ number_format($order->total_returned_billed, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">Net after returns:</th>
                            <th>{{ number_format($order->net_total, 2) }}</th>
                        </tr>
                        @endif
                        @if($order->total_refunded > 0)
                        <tr>
                            <th colspan="6" class="text-end text-danger">Refunded:</th>
                            <th class="text-danger">-{{ number_format($order->total_refunded, 2) }}</th>
                        </tr>
                        @endif
                        @if(($order->claim_credit_amount ?? 0) > 0)
                        <tr>
                            <th colspan="6" class="text-end text-info">Return claim applied:</th>
                            <th class="text-info">-{{ number_format((float) $order->claim_credit_amount, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="6" class="text-end">Due Amount:</th>
                            <th class="text-danger">{{ number_format($order->due_amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($order->saleReturns->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sale Returns</h5>
                <a href="{{ route('sale-returns.index', ['order_no' => $order->order_number]) }}" class="btn btn-sm btn-outline-secondary">All returns</a>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($order->saleReturns as $ret)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('sale-returns.show', $ret) }}" class="fw-semibold">{{ $ret->return_no }}</a>
                            <span class="text-muted small ms-2">{{ $ret->return_date->format('Y-m-d') }}</span>
                            @if($ret->reason)<div class="small text-muted">{{ $ret->reason }}</div>@endif
                        </div>
                        <span class="text-warning fw-semibold">−{{ number_format((float) $ret->total, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Document history (audit trail) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> History</h5>
                <span class="small text-muted">Who created, edited, issued, printed or paid this order</span>
            </div>
            <div class="card-body">
                @php
                    $historyLogs = \Illuminate\Support\Facades\Schema::hasTable('audit_logs')
                        ? \App\Models\AuditLog::query()
                            ->where(function ($q) use ($order) {
                                $q->where(fn ($w) => $w->where('auditable_type', \App\Models\Order::class)->where('auditable_id', $order->id))
                                  ->orWhere(fn ($w) => $w->where('auditable_type', \App\Models\Payment::class)->whereIn('auditable_id', $order->payments->pluck('id')))
                                  ->orWhere(fn ($w) => $w->where('auditable_type', \App\Models\OrderItem::class)->whereIn('auditable_id', $order->items->pluck('id')));
                            })
                            ->orderByDesc('id')->limit(100)->get()
                        : collect();
                @endphp
                @include('accounting::phase6.partials.history', ['logs' => $historyLogs])
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5>Payment History</h5>
            </div>
            <div class="card-body">
                @if($order->payments->isEmpty())
                    <p class="text-muted">No payments recorded.</p>
                @else
                    <table class="table table-striped table-sm align-middle">
                        @if(session('reminder_success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('reminder_success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if(session('reminder_error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('reminder_error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        <thead>
                            <tr>
                                <th>Payment date</th>
                                <th>Method</th>
                                <th>Cheque date</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Cheque / photo</th>
                                <th>Notes</th>
                                <th>Remind</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>
                                    @if(strtolower($payment->payment_method) === 'cheque')
                                        <strong>{{ $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}</strong>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $payment->reference_number ?? '-' }}</td>
                                <td>
                                    @if($payment->isRefund())
                                        <span class="text-danger">−{{ number_format($payment->amount, 2) }}</span>
                                        <span class="badge bg-danger ms-1">Refund</span>
                                    @else
                                        {{ number_format($payment->amount, 2) }}
                                        @if($payment->cashNextPaymentDiscountLine())
                                            <div class="small text-success">{{ $payment->cashNextPaymentDiscountLine() }}</div>
                                        @elseif(($payment->discount_amount ?? 0) > 0)
                                            <div class="small text-danger">Disc −{{ number_format($payment->discount_amount, 2) }}
                                                @if($payment->discount_type === 'percentage')
                                                    ({{ number_format($payment->discount_value, 2) }}%)
                                                @endif
                                            </div>
                                            <div class="small text-muted">Settled {{ number_format($payment->effectiveSettlementAmount(), 2) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($payment->isRefund())
                                        <span class="badge bg-danger">Refund</span>
                                    @elseif($payment->isCheque())
                                        @include('erp.payment-management._cheque-status', ['payment' => $payment])
                                    @else
                                        <span class="badge bg-success">Paid</span>
                                    @endif
                                </td>
                                <td>
                                    @if(strtolower($payment->payment_method) === 'cheque')
                                        @if($payment->cheque_image_path)
                                            <a href="{{ route('order-payments.cheque-image', $payment) }}" target="_blank" rel="noopener">View photo</a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                        @if($payment->cheque_exception_requested)
                                            <br><span class="badge bg-warning text-dark">Exception</span>
                                        @endif
                                        @if(filled($payment->cheque_exception_reason))
                                            <p class="small text-muted mb-0 mt-1">{{ $payment->cheque_exception_reason }}</p>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $payment->notes ?? '—' }}</td>
                                <td>
                                    @if(strtolower($payment->payment_method) === 'cheque')
                                        @php
                                            $cName = $order->customer->display_name ?? 'Customer';
                                            $cDate = $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—';
                                            $cRef  = $payment->reference_number ?: '—';
                                            $cAmt  = number_format((float)$payment->amount, 2);
                                            $defaultMsg = "Dear {$cName},\n\nThis is a reminder about your cheque payment for order {$order->order_number}.\nCheque date: {$cDate}\nReference: {$cRef}\nAmount: Rs {$cAmt}\n\nPlease ensure the cheque is ready. Thank you.\n\n" . config('app.name');
                                        @endphp
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#remindModal{{ $payment->id }}"
                                            title="Send reminder to customer">
                                            <i class="fas fa-bell"></i> Remind
                                        </button>

                                        <!-- Reminder Modal -->
                                        <div class="modal fade" id="remindModal{{ $payment->id }}" tabindex="-1" aria-labelledby="remindModalLabel{{ $payment->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('order-payments.remind', $payment) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="remindModalLabel{{ $payment->id }}">
                                                                Send Reminder to Customer
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="text-muted small mb-3">
                                                                Customer: <strong>{{ $cName }}</strong> &bull;
                                                                Cheque date: <strong>{{ $cDate }}</strong> &bull;
                                                                Ref: <strong>{{ $cRef }}</strong>
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Message</label>
                                                                <textarea name="message" class="form-control" rows="8" required>{{ $defaultMsg }}</textarea>
                                                                <small class="text-muted">You can edit this message before sending.</small>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label fw-semibold">Send via</label><br>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="rch_wa_{{ $payment->id }}" checked>
                                                                    <label class="form-check-label" for="rch_wa_{{ $payment->id }}">WhatsApp</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="rch_sms_{{ $payment->id }}" checked>
                                                                    <label class="form-check-label" for="rch_sms_{{ $payment->id }}">SMS</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="rch_email_{{ $payment->id }}" checked>
                                                                    <label class="form-check-label" for="rch_email_{{ $payment->id }}">Email</label>
                                                                </div>
                                                            </div>
                                                            @if(!$order->customer?->phone && !$order->customer?->email)
                                                                <div class="alert alert-warning py-1 small mt-2">
                                                                    No phone or email on customer record — reminder may not reach them.
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-paper-plane"></i> Send Reminder
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    @include('erp.orders.partials.add-payment-card', [
        'formAction' => route('orders.payment.store', $order->id),
        'isAdminView' => true,
        'showUsageGuide' => true,
    ])
</div>

<div class="modal fade" id="orderCheckModal" tabindex="-1" aria-labelledby="orderCheckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <form method="POST" action="{{ route('orders.complete_order_check', $order->id) }}" id="issue-invoice-form" class="modal-content">
            @csrf
            @php $checkProgress = $order->orderCheckProgress(); @endphp
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="orderCheckModalLabel">Order Check - {{ $order->order_number }}</h5>
                    <div class="small mt-1">
                        <span class="badge bg-success">Checked {{ $checkProgress['checked'] }}</span>
                        <span class="badge bg-warning text-dark">Left {{ $checkProgress['left'] }}</span>
                        <span class="text-muted">/ {{ $checkProgress['total'] }}</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    සියලුම item check කරලා qty confirm/සංස්කරණය කරලා පසුව invoice issue කරන්න.
                </div>
                <div class="border rounded p-2 mb-3" style="background:#f4f7fb;">
                    <div class="fw-semibold mb-2">Order summary</div>
                    <div class="small mb-2">
                        <div><strong>Business:</strong> {{ filled($order->customer?->business_name) ? $order->customer->business_name : ($order->customer?->display_name ?? 'N/A') }}</div>
                        @if($order->customer?->name && filled($order->customer?->business_name) && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                            <div><strong>Owner:</strong> {{ $order->customer->name }}</div>
                        @endif
                        <div>
                            <strong>Sales person:</strong>
                            {{ ($order->salesPerson && $order->salesPerson->role === 'sales_person') ? $order->salesPerson->name : '—' }}
                            · <strong>Date:</strong> {{ $order->order_date?->format('Y-m-d') ?? '—' }}
                        </div>
                        @if(filled($order->notes))
                            <div class="mt-1"><strong>Note:</strong> <span style="white-space: pre-wrap;">{{ $order->notes }}</span></div>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                            <thead>
                                <tr>
                                    <th>Ordered product</th>
                                    <th class="text-end" style="width:14%">Ordered qty</th>
                                    <th class="text-end" style="width:14%">Check qty</th>
                                    <th class="text-end" style="width:16%">Price</th>
                                    <th class="text-end" style="width:18%">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    @php
                                        $lineAmount = round((float) $item->unit_price * (float) $item->quantity, 2);
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ optional($item->product)->name ?? 'Product' }}
                                            @if($item->variation)
                                                <small class="d-block text-muted">({{ $item->variation->variation_name }})</small>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format($item->orderedQuantity(), 2), '0'), '.') }}</td>
                                        <td class="text-end">{{ $item->isChecked() ? rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') : '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="text-end">{{ number_format($lineAmount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                @if(($order->discount_amount ?? 0) > 0)
                                    <tr>
                                        <th colspan="4" class="text-end">Discount</th>
                                        <th class="text-end">-{{ number_format((float) $order->discount_amount, 2) }}</th>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="4" class="text-end">Order total <span class="fw-normal text-muted small">(check qty අනුව)</span></th>
                                    <th class="text-end">{{ number_format((float) $order->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:5%">Check</th>
                                <th>Product</th>
                                <th class="text-end" style="width:12%">Ordered qty</th>
                                <th style="width:14%">Unit Price</th>
                                <th style="width:14%">Qty</th>
                                <th class="text-end" style="width:14%">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @php $alreadyChecked = $item->isChecked(); @endphp
                                <tr class="{{ $alreadyChecked ? 'table-success' : '' }} check-line-row">
                                    <td class="text-center">
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="hidden" name="items[{{ $loop->index }}][checked]" value="{{ $alreadyChecked ? 1 : 0 }}">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            @if($alreadyChecked)
                                                <input type="checkbox" class="form-check-input" checked disabled>
                                                <span class="small fw-semibold text-success">Checked</span>
                                            @else
                                                <input type="checkbox" class="form-check-input issue-check order-check-checkbox" name="items[{{ $loop->index }}][checked]" value="1">
                                                <span class="small text-muted">OK</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ optional($item->product)->name }}
                                        @if($item->variation)
                                            <small class="d-block text-muted">({{ $item->variation->variation_name }})</small>
                                        @endif
                                        @if($alreadyChecked)
                                            <span class="badge bg-success mt-1">Checked</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($item->orderedQuantity(), 2), '0'), '.') }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm issue-price" value="{{ number_format($item->unit_price, 2, '.', '') }}" readonly>
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ number_format((float)($stockInsights[$item->id]['available'] ?? 0), 2, '.', '') }}"
                                            data-max-stock="{{ number_format((float)($stockInsights[$item->id]['available'] ?? 0), 2, '.', '') }}"
                                            name="items[{{ $loop->index }}][quantity]"
                                            class="form-control form-control-sm issue-qty"
                                            value="{{ $alreadyChecked ? number_format((float) $item->quantity, 2, '.', '') : number_format(min((float)$item->quantity, (float)($stockInsights[$item->id]['available'] ?? 0)), 2, '.', '') }}"
                                            @if($alreadyChecked) readonly @endif
                                        >
                                        <small class="text-muted">Available: {{ number_format((float)($stockInsights[$item->id]['available'] ?? 0), 2) }}</small>
                                    </td>
                                    <td class="text-end fw-semibold check-line-amount">{{ number_format((float) $item->unit_price * (float) $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Check total (යවන qty අනුව)</th>
                                <th class="text-end" id="order-check-live-total">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Issued Bags Count</label>
                        <input type="number" min="0" step="1" name="issued_bags_count" class="form-control" value="{{ old('issued_bags_count', $order->issued_bags_count ?? 0) }}" autocomplete="off">
                        <small class="text-muted">Optional. Bags නැත්නම් 0 දාන්න.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Issued Bundle Count</label>
                        <input type="number" min="0" step="1" name="issued_bundle_count" class="form-control" value="{{ old('issued_bundle_count', $order->issued_bundle_count ?? 0) }}" autocomplete="off">
                        <small class="text-muted">Optional. Bundles නැත්නම් 0 දාන්න.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="save-order-check-btn">Save checked items</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="orderUsageGuideModal" tabindex="-1" aria-labelledby="orderUsageGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderUsageGuideModalLabel">Order Section භාවිතා කරන විදිහ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ol class="mb-0">
                    <li class="mb-2"><strong>Order Info බලන්න:</strong> උඩින් Customer, Sales Person, Date, Status බලන්න.</li>
                    <li class="mb-2"><strong>Stock තත්ත්වය බලන්න:</strong> Items table එකේ `Stock / නිෂ්පාදන තත්ත්වය` column එකෙන් shortage තියෙනවාද බලන්න.</li>
                    <li class="mb-2"><strong>Invoice issue කරන්න:</strong> Order completed නැත්නම් `Order Check` button එක click කරලා popup එකෙන් item tick කර qty confirm කරන්න.</li>
                    <li class="mb-2"><strong>Qty edit नियम:</strong> Available stock ට වඩා වැඩි qty දාන්න බැහැ. අඩු qty දාන්න පුළුවන්.</li>
                    <li class="mb-2"><strong>Discount දාන්න:</strong> Order Check popup එකේ Discount Type/Value දාලා final total check කරන්න.</li>
                    <li class="mb-2"><strong>Issue Invoice:</strong> `Issue Invoice` click කළාම order completed වෙලා stock auto අඩු වෙනවා.</li>
                    <li class="mb-2"><strong>Download Invoice:</strong> Invoice issue උන පස්සේ මේ page එකෙන් `Download Invoice` button එකෙන් print/download කරන්න.</li>
                    <li><strong>Payments:</strong> Order Check complete කරලා After Checking වුණාම පමණක් දකුණේ `Add Payment` section එකෙන් payment lines add කරන්න.</li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">වසන්න</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('issue-invoice-form');
    if (!form) return;

    var qtyInputs = form.querySelectorAll('.issue-qty');

    function toNumber(val) {
        var num = parseFloat(val);
        return isNaN(num) ? 0 : num;
    }

    function enforceQtyMax() {
        for (var i = 0; i < qtyInputs.length; i++) {
            if (qtyInputs[i].readOnly) continue;
            var qty = toNumber(qtyInputs[i].value);
            var maxStock = toNumber(qtyInputs[i].getAttribute('data-max-stock'));
            if (qty > maxStock) {
                qty = maxStock;
                qtyInputs[i].value = maxStock.toFixed(2);
            }
        }
    }

    qtyInputs.forEach(function(input) { input.addEventListener('input', function() { enforceQtyMax(); refreshCheckAmounts(); }); });
    enforceQtyMax();

    var saveBtn = document.getElementById('save-order-check-btn');
    var boxes = form.querySelectorAll('.issue-check');
    function refreshCheckButton() {
        if (!saveBtn) return;
        var pending = boxes.length;
        var ticked = form.querySelectorAll('.issue-check:checked').length;
        saveBtn.textContent = pending > 0 && ticked === pending ? 'Send to Order AC' : 'Save checked items';
    }
    boxes.forEach(function(box) { box.addEventListener('change', refreshCheckButton); });
    refreshCheckButton();

    function refreshCheckAmounts() {
        var total = 0;
        form.querySelectorAll('.check-line-row').forEach(function(row) {
            var qtyInput = row.querySelector('.issue-qty');
            var priceInput = row.querySelector('.issue-price');
            var amountCell = row.querySelector('.check-line-amount');
            var qty = toNumber(qtyInput && qtyInput.value);
            var price = toNumber(priceInput && priceInput.value);
            var amount = Math.round(qty * price * 100) / 100;
            if (amountCell) amountCell.textContent = amount.toFixed(2);
            total += amount;
        });
        var liveTotal = document.getElementById('order-check-live-total');
        if (liveTotal) liveTotal.textContent = total.toFixed(2);
    }
    form.querySelectorAll('.issue-qty').forEach(function(input) {
        input.addEventListener('input', refreshCheckAmounts);
        input.addEventListener('change', refreshCheckAmounts);
    });
    refreshCheckAmounts();

    form.addEventListener('submit', function(e) {
        var rows = form.querySelectorAll('tbody tr');
        for (var i = 0; i < rows.length; i++) {
            var box = rows[i].querySelector('.issue-check');
            var qtyInput = rows[i].querySelector('.issue-qty');
            if (!box || !box.checked || !qtyInput || qtyInput.readOnly) continue;
            if (toNumber(qtyInput.value) < 0) {
                e.preventDefault();
                alert('Check කරන item එකේ qty එක ඍණ වෙන්න බැහැ.');
                qtyInput.focus();
                return;
            }
        }
    });
})();
</script>

@php $invoiceSubtotalModal = (float) $order->items->sum('total_price'); @endphp
@if($order->status === 'afterchecking')
<div class="modal fade" id="issueInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('orders.issue_invoice', $order->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Issue Invoice — {{ $order->order_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('erp.orders.partials.invoice-financial-fields', [
                        'order' => $order,
                        'subtotal' => $invoiceSubtotalModal,
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Issue Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($order->status === 'completed')
<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            @canRoute('orders.update_invoice')
<form method="POST" action="{{ route('orders.update_invoice', $order->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Invoice — {{ $order->invoice_number ?? $order->order_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @error('invoice_edit')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    <p class="small text-muted">Transport හෝ වෙනත් expenses දාලා net total එක update කරන්න. Stock නැවත change නොවේ.</p>
                    @include('erp.orders.partials.invoice-financial-fields', [
                        'order' => $order,
                        'subtotal' => $invoiceSubtotalModal,
                        'showClaimCredit' => false,
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Save invoice changes</button>
                </div>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endif

@if($order->canAcceptSaleReturn() && $order->items->contains(fn ($i) => $i->returnableQuantity() > 0))
<div class="modal fade" id="saleReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sale Return — {{ $order->order_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('erp.sale-returns._form', [
                    'order' => $order,
                    'formAction' => route('orders.returns.store', $order),
                    'hiddenFields' => ['redirect_order_show' => 1],
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="sale-return-form-{{ $order->id }}" class="btn btn-warning">Submit Sale Return</button>
            </div>
        </div>
    </div>
</div>
@endif

@include('erp.orders.partials.invoice-financial-script')
@if($order->due_amount > 0 && $order->canAcceptPayments())
    @include('erp.sales_persons.orders.partials.payment-form-scripts')
@endif
@endsection
