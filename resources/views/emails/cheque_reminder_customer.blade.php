<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cheque payment reminder</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #1e3a5f; border-bottom: 2px solid #1e3a5f; padding-bottom: 8px;">
        {{ config('app.name') }} — Cheque Payment Reminder
    </h2>
    <p>Dear <strong>{{ $customer->name }}</strong>,</p>
    <div style="background: #f8fafc; border-left: 4px solid #1e3a5f; padding: 14px 18px; margin: 16px 0; border-radius: 0 6px 6px 0;">
        {!! nl2br(e($messageBody)) !!}
    </div>
    <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
        <tr>
            <td style="padding: 6px 10px; background: #1e3a5f; color: #fff; font-weight: bold; width: 40%;">Order</td>
            <td style="padding: 6px 10px; border-bottom: 1px solid #e2e8f0;">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #1e3a5f; color: #fff; font-weight: bold;">Amount</td>
            <td style="padding: 6px 10px; border-bottom: 1px solid #e2e8f0;">Rs {{ number_format((float)$payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #1e3a5f; color: #fff; font-weight: bold;">Cheque date</td>
            <td style="padding: 6px 10px; border-bottom: 1px solid #e2e8f0;"><strong>{{ $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #1e3a5f; color: #fff; font-weight: bold;">Reference / cheque no.</td>
            <td style="padding: 6px 10px;">{{ $payment->reference_number ?: '—' }}</td>
        </tr>
    </table>
    <p style="margin-top: 24px; font-size: 13px; color: #64748b;">
        Thank you,<br>
        <strong>{{ config('app.name') }}</strong>
    </p>
</body>
</html>
