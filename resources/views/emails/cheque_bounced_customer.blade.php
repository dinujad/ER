<p>Dear {{ $customer->name }},</p>

<p>We regret to inform you that the cheque payment for order <strong>{{ $order->order_number }}</strong> has been <strong>returned by the bank</strong>.</p>

<ul>
    <li><strong>Amount:</strong> Rs {{ number_format((float) $payment->amount, 2) }}</li>
    <li><strong>Cheque date:</strong> {{ $payment->cheque_date ? $payment->cheque_date->format('d M Y') : '—' }}</li>
    <li><strong>Reference:</strong> {{ $payment->reference_number ?: '—' }}</li>
    <li><strong>Reason:</strong> {{ $reason }}</li>
</ul>

<p>Your invoice is now <strong>unpaid again</strong>. Please contact us to arrange an alternative payment (cash or bank transfer) at your earliest convenience.</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
