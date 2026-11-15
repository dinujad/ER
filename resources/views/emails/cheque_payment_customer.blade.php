<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cheque payment</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333;">
    <p>Hello {{ $customer->name }},</p>
    <p>We have recorded a <strong>cheque</strong> payment against your order <strong>{{ $order->order_number }}</strong>.</p>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ddd;">
        <tr><td>Amount</td><td>Rs {{ number_format((float) $payment->amount, 2) }}</td></tr>
        <tr><td>Payment date</td><td>{{ $payment->payment_date->format('Y-m-d') }}</td></tr>
        <tr><td><strong>Cheque date</strong></td><td>{{ $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}</td></tr>
        <tr><td>Reference / cheque no.</td><td>{{ $payment->reference_number ?: '—' }}</td></tr>
    </table>
    @if(!empty(trim($customer->address ?? '')))
        <p><strong>Your details on file</strong><br>{{ $customer->address }}<br>
        @if(!empty(trim($customer->phone ?? '')))Phone: {{ $customer->phone }}@endif</p>
    @endif
    <p>Thank you,<br>{{ config('app.name') }}</p>
</body>
</html>
