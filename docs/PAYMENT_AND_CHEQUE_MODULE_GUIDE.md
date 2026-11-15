# DERMAS ERP — Payment Management & Cheque Payment Module
**User Guide for Client | Version 1.0**

---

## 1. Overview

The **Payment Management** module in DERMAS ERP handles all **customer order payments** — cash, bank transfer, card, and **cheque**. It helps your team:

- Track which orders are **paid** or **due**
- Record one or multiple payments per order
- Manage the full **cheque lifecycle** (received → deposited → cleared → bounced)
- Send automatic **WhatsApp / SMS / Email** alerts for cheque payments
- Post accounting entries to **PDC (Post-Dated Cheque)** and **Bank** accounts

**ERP Menu path:** Payment Management → Order payments / Cheque payments

---

## 2. When Can Payments Be Recorded?

Payments can only be added when the order status is:

- **After Checking** (order check completed), or
- **Completed** (invoice issued)

Before order check, the payment form is blocked — this prevents recording payments before quantities are verified.

**Who can record payments:**
- **Admin** — from Order detail page or Payment Management
- **Sales Person** — from their order view (only their orders)

---

## 3. Payment Methods Supported

| Method | Description |
|--------|-------------|
| **Cash** | Immediate cash receipt |
| **Bank Transfer** | Direct bank deposit reference |
| **Card** | Card payment |
| **Cheque** | Post-dated or current cheque — full workflow below |

### Multi-payment per order
One order can have **multiple payment lines** in a single save (e.g. Rs 50,000 cash + Rs 100,000 cheque). Total cannot exceed the order due amount.

---

## 4. Cheque Payment — Full Workflow

### Step 1: Cheque Received (PDC)
When a customer gives a cheque and it is recorded in the system:

- **Status:** `Received (PDC)`
- **Business effect:** Customer invoice shows as paid (due amount reduced)
- **Accounting:** Debit **PDC / Uncleared Funds (GL 1150)** → Credit **Accounts Receivable (GL 1200)**
- Money does **NOT** go to the bank account yet
- **Automatic notifications sent:**
  - Admin — WhatsApp (with cheque photo if uploaded)
  - Sales Person — WhatsApp
  - Customer — SMS + Email

**Required when recording cheque:**
- Cheque date (normally today to 45 days ahead; exception with English reason for longer dates)
- Cheque photo (JPG, PNG, WEBP, HEIC, PDF — max 10MB)
- Payment amount and reference number

### Step 2: Bank Deposit
When the cheque is physically deposited at the bank:

- Admin clicks **Deposit** on the cheque row
- **Status:** `Deposited`
- Records deposit date — operational tracking only

### Step 3: Cheque Cleared
When the bank confirms funds are credited:

- Admin clicks **Clear**
- **Status:** `Cleared`
- **Accounting:** Debit **Bank Account (GL 1010)** → Credit **PDC (GL 1150)**
- Payment process is complete

### Step 4: Cheque Returned / Bounced
If the bank returns the cheque (insufficient funds, wrong signature, wrong date, etc.):

- Admin clicks **Return** and enters the reason
- **Status:** `Returned / Bounced`
- **Business effect:** Invoice becomes **unpaid again** (due amount restored)
- **Accounting:** Original entries are reversed
- **Automatic notifications:**
  - Admin + Sales Person — WhatsApp alert
  - Customer — SMS + Email asking to arrange alternative payment (cash/bank transfer)

---

## 5. Payment Management Screens

### 5.1 Order Payments (`/erp/payment-management`)
- Lists all orders with payment status
- Filters: order number, status, sales person, customer, date range, paid/due
- KPIs: due order count, total outstanding amount
- Click order → open order detail → add payment

### 5.2 Cheque Payments (`/erp/payment-management/cheque-payments`)
- One row per cheque payment
- Filters: cheque date range, payment date, customer, status (Received / Deposited / Cleared / Bounced)
- Actions: **Deposit**, **Clear**, **Return**
- View cheque photo

### 5.3 Order Detail — Payment History
Each order shows all payments with:
- Payment date, method, amount, reference
- Cheque date and photo link
- Cheque status and action buttons
- **Remind** button — send manual reminder to customer (WhatsApp / SMS / Email)

---

## 6. Automatic Cheque Reminders (Scheduled)

The system runs a daily job (`cheque:send-due-reminders`):

| Trigger | Who is notified |
|---------|-----------------|
| **On cheque date** | Admin + Sales Person (WhatsApp) |
| **X days before cheque date** | Customer (WhatsApp / SMS / Email — configurable in Settings) |

**Settings:** ERP → Cheque & WhatsApp Settings

---

## 7. Purchase Payment Management (Suppliers)

Separate module for **supplier/purchase** payments:

- **Purchase Payment Management** — purchase dues, payments, cheque tracking for suppliers
- Path: ERP → Purchases → Purchase Payment Management

*(Sales order cheques and purchase cheques are managed in separate screens.)*

---

## 8. Accounting Integration

| Event | Debit | Credit |
|-------|-------|--------|
| Cheque received | PDC / Uncleared (1150) | Accounts Receivable (1200) |
| Cheque cleared | Bank (1010) | PDC (1150) |
| Cheque bounced | Entries reversed | — |

Requires Chart of Accounts seeded (`php artisan db:seed --class=ChartOfAccountsSeeder`).

---

## 9. User Roles & Permissions

| Permission | Access |
|------------|--------|
| `orders.view` | View Payment Management — Order payments |
| `orders.payment` | Record payments, cheque deposit/clear/bounce, send reminders |
| `cheque.settings` | Cheque & WhatsApp notification settings |

---

## 10. Quick Reference — Daily Operations

| Task | Where to go |
|------|-------------|
| See all due orders | Payment Management → Order payments → Filter: Due |
| See all cheques due today | Cheque payments → Filter cheque date = today |
| Record new payment | Order detail → Add Payment |
| Deposit cheque to bank | Cheque payments → Deposit button |
| Mark cheque cleared | Cheque payments → Clear button |
| Handle bounced cheque | Cheque payments → Return → enter reason |
| Send customer reminder | Order detail → Payment History → Remind |
| Configure notifications | Settings → Cheque & WhatsApp |

---

## 11. Support Notes

- Cheque photos are stored securely and viewable only by authorized users
- Bounced cheques are **excluded** from paid amount calculations automatically
- For server deployment after updates: `php artisan migrate` + `php artisan route:cache`

---

**DERMAS Apparel ERP** | Payment & Cheque Module Guide  
*Document generated for client handover*
