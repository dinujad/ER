<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DERMAS ERP — Payment & Cheque Module Guide</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; margin: 0; padding: 0; }
        .cover { text-align: center; padding: 80px 40px 60px; background: #1e3a5f; color: #fff; page-break-after: always; }
        .cover h1 { font-size: 28px; margin: 0 0 10px; }
        .cover h2 { font-size: 16px; font-weight: normal; opacity: 0.9; margin: 0 0 30px; }
        .cover .meta { font-size: 12px; opacity: 0.8; }
        h2 { color: #1e3a5f; font-size: 16px; border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; margin-top: 24px; }
        h3 { color: #2d5a87; font-size: 13px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; }
        .flow-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin: 10px 0; }
        .step { display: inline-block; background: #1e3a5f; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 10px; margin-right: 6px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-received { background: #cff4fc; color: #055160; }
        .badge-deposited { background: #cfe2ff; color: #084298; }
        .badge-cleared { background: #d1e7dd; color: #0f5132; }
        .badge-bounced { background: #f8d7da; color: #842029; }
        ul { margin: 6px 0; padding-left: 20px; }
        li { margin-bottom: 4px; }
        .page-break { page-break-before: always; }
        .footer { text-align: center; font-size: 9px; color: #666; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

<div class="cover">
    <h1>DERMAS ERP</h1>
    <h2>Payment Management &amp; Cheque Payment Module</h2>
    <p class="meta">User Guide for Client</p>
    <p class="meta">Version 1.0 | {{ now()->format('F Y') }}</p>
</div>

<h2>1. Overview</h2>
<p>The <strong>Payment Management</strong> module handles all customer order payments — cash, bank transfer, card, and cheque. It tracks paid/due orders, manages the full cheque lifecycle, sends automatic notifications, and posts accounting entries.</p>
<p><strong>Menu:</strong> ERP → Payment Management → Order payments / Cheque payments</p>

<h2>2. When Payments Can Be Recorded</h2>
<p>Payments are allowed only after <strong>Order Check</strong> is completed (status: After Checking or Completed). Admin and Sales Persons can record payments from the order detail page.</p>

<h2>3. Payment Methods</h2>
<table>
    <tr><th>Method</th><th>Description</th></tr>
    <tr><td>Cash</td><td>Immediate cash receipt</td></tr>
    <tr><td>Bank Transfer</td><td>Direct bank deposit with reference</td></tr>
    <tr><td>Card</td><td>Card payment</td></tr>
    <tr><td>Cheque</td><td>Full lifecycle workflow — see Section 4</td></tr>
</table>
<p>Multiple payment lines can be saved in one transaction (e.g. partial cash + cheque). Total cannot exceed order due amount.</p>

<h2>4. Cheque Payment — Complete Workflow</h2>

<div class="flow-box">
    <p><span class="step">1</span> <span class="badge badge-received">Received (PDC)</span></p>
    <ul>
        <li>Cheque recorded with date, amount, reference, and photo</li>
        <li>Customer due reduced — invoice shows as paid</li>
        <li>Accounting: DR PDC (Uncleared) → CR Accounts Receivable</li>
        <li>Auto notify: Admin (WA), Sales Person (WA), Customer (SMS + Email)</li>
    </ul>
</div>

<div class="flow-box">
    <p><span class="step">2</span> <span class="badge badge-deposited">Deposited</span></p>
    <ul>
        <li>Admin marks cheque as deposited when sent to bank</li>
        <li>Status update for tracking — no accounting change</li>
    </ul>
</div>

<div class="flow-box">
    <p><span class="step">3</span> <span class="badge badge-cleared">Cleared</span></p>
    <ul>
        <li>Bank confirms funds credited</li>
        <li>Accounting: DR Bank Account → CR PDC</li>
        <li>Payment process complete</li>
    </ul>
</div>

<div class="flow-box">
    <p><span class="step">4</span> <span class="badge badge-bounced">Returned / Bounced</span></p>
    <ul>
        <li>Bank returns cheque — admin enters reason</li>
        <li>Invoice becomes unpaid again (due restored)</li>
        <li>Accounting entries reversed</li>
        <li>Auto notify: Admin, Sales Person, Customer to arrange new payment</li>
    </ul>
</div>

<div class="page-break"></div>

<h2>5. ERP Screens</h2>

<h3>5.1 Order Payments</h3>
<p>Lists all orders with payment status. Filter by order no, customer, sales person, date, paid/due. Shows total outstanding KPI.</p>

<h3>5.2 Cheque Payments</h3>
<p>One row per cheque. Filter by cheque date, status. Actions: Deposit, Clear, Return. View cheque photo.</p>

<h3>5.3 Order Detail — Payment History</h3>
<p>All payments per order with status, cheque photo, and manual Remind button for customers.</p>

<h2>6. Automatic Reminders</h2>
<table>
    <tr><th>Trigger</th><th>Recipients</th></tr>
    <tr><td>On cheque date</td><td>Admin + Sales Person (WhatsApp)</td></tr>
    <tr><td>X days before cheque date</td><td>Customer (WhatsApp / SMS / Email)</td></tr>
</table>
<p>Configure in: ERP → Settings → Cheque &amp; WhatsApp Settings</p>

<h2>7. Accounting Entries</h2>
<table>
    <tr><th>Event</th><th>Debit</th><th>Credit</th></tr>
    <tr><td>Cheque received</td><td>PDC / Uncleared (1150)</td><td>Accounts Receivable (1200)</td></tr>
    <tr><td>Cheque cleared</td><td>Bank (1010)</td><td>PDC (1150)</td></tr>
    <tr><td>Cheque bounced</td><td colspan="2">Original entries reversed</td></tr>
</table>

<h2>8. Daily Quick Reference</h2>
<table>
    <tr><th>Task</th><th>Location</th></tr>
    <tr><td>View due orders</td><td>Payment Management → Order payments → Due filter</td></tr>
    <tr><td>Cheques due today</td><td>Cheque payments → Cheque date = today</td></tr>
    <tr><td>Record payment</td><td>Order detail → Add Payment</td></tr>
    <tr><td>Deposit cheque</td><td>Cheque payments → Deposit</td></tr>
    <tr><td>Clear cheque</td><td>Cheque payments → Clear</td></tr>
    <tr><td>Bounced cheque</td><td>Cheque payments → Return + reason</td></tr>
    <tr><td>Customer reminder</td><td>Order → Payment History → Remind</td></tr>
</table>

<div class="footer">
    DERMAS Apparel ERP — Payment &amp; Cheque Module Guide | Confidential — For client use only
</div>

</body>
</html>
