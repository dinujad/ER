<?php

namespace App\Services;

use App\Mail\ChequePaymentCustomerMail;
use App\Models\AppSetting;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ChequePaymentNotifier
{
    /** WhatsApp image captions are safest under ~1020 chars. */
    private const MAX_IMAGE_CAPTION = 1020;

    public function __construct(
        protected HostGrapWhatsAppService $whatsapp,
        protected TextLkSmsService $sms,
    ) {}

    /**
     * Fired when a new cheque payment is saved (immediate alerts).
     */
    public function notifyNewCheque(Payment $payment): void
    {
        $payment->loadMissing(['order.customer', 'order.salesPerson.employee']);
        $order = $payment->order;
        if (!$order) {
            return;
        }

        $customer = $order->customer;
        $salesUser = $order->salesPerson;
        $photoUrl = $this->signedChequePhotoUrl($payment);

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_ADMIN_WA, true)) {
            $adminPhone = AppSetting::get(AppSetting::KEY_HOSTGRAP_ADMIN_PHONE) ?: config('services.hostgrap.admin_phone');
            if ($adminPhone) {
                $body = $this->buildAdminNewChequeWhatsapp($payment, $order, $customer, $salesUser);
                if ($photoUrl) {
                    $body = "*Cheque copy attached as image.*\n\n".$body;
                }
                $this->pushChequeWhatsapp($adminPhone, $photoUrl, $body);
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_SALES_WA, true) && $salesUser) {
            $spPhone = $salesUser->employee?->contact;
            if ($spPhone) {
                $body = $this->buildSalesNewChequeWhatsapp($payment, $order, $customer);
                if ($photoUrl) {
                    $body = "*Cheque copy attached as image.*\n\n".$body;
                }
                $this->pushChequeWhatsapp($spPhone, $photoUrl, $body);
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_CUSTOMER_SMS, true) && $customer && !empty(trim($customer->phone ?? ''))) {
            $msg = $this->buildCustomerSms($payment, $order, $customer);
            try {
                $this->sms->sendSms($customer->phone, $msg);
            } catch (\Throwable $e) {
                Log::warning('Cheque customer SMS failed: '.$e->getMessage());
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_CUSTOMER_EMAIL, true) && $customer && !empty(trim($customer->email ?? ''))) {
            try {
                Mail::to($customer->email)->send(new ChequePaymentCustomerMail($payment, $order, $customer));
            } catch (\Throwable $e) {
                Log::warning('Cheque customer email failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Same-day cheque date reminder (scheduled job).
     */
    public function notifyChequeDueToday(Payment $payment): void
    {
        $payment->loadMissing(['order.customer', 'order.salesPerson.employee']);
        $order = $payment->order;
        if (!$order) {
            return;
        }

        $customer = $order->customer;
        $salesUser = $order->salesPerson;
        $photoUrl = $this->signedChequePhotoUrl($payment);

        $chqStr = $payment->cheque_date?->format('Y-m-d') ?? '—';
        $intro = '*Reminder:* Cheque *date is today* ('.$chqStr.'). Follow up / bank if needed.';

        $body = $this->buildReminderWhatsapp($payment, $order, $customer, $salesUser, $intro);
        if ($photoUrl) {
            $body = "*Cheque copy attached as image.*\n\n".$body;
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_ADMIN_WA, true)) {
            $adminPhone = AppSetting::get(AppSetting::KEY_HOSTGRAP_ADMIN_PHONE) ?: config('services.hostgrap.admin_phone');
            if ($adminPhone) {
                $this->pushChequeWhatsapp($adminPhone, $photoUrl, $body);
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_SALES_WA, true) && $salesUser) {
            $spPhone = $salesUser->employee?->contact;
            if ($spPhone) {
                $this->pushChequeWhatsapp($spPhone, $photoUrl, $body);
            }
        }
    }

    /**
     * Auto customer reminder (scheduled: X days before cheque_date).
     * Sends via the channels configured under KEY_CUSTOMER_REMIND_CHANNELS (default: whatsapp,sms,email).
     *
     * @param  string|null  $customMessage  Override the default message body (used for manual reminders too).
     * @param  array<string>  $forceChannels  If non-empty, use these channels instead of settings (manual send).
     */
    public function notifyCustomerReminder(Payment $payment, ?string $customMessage = null, array $forceChannels = []): array
    {
        $payment->loadMissing(['order.customer', 'order.salesPerson.employee']);
        $order = $payment->order;

        $results = ['whatsapp' => null, 'sms' => null, 'email' => null];

        if (!$order) {
            return $results;
        }

        $customer = $order->customer;
        if (!$customer) {
            return $results;
        }

        $channels = $forceChannels ?: array_map(
            'trim',
            explode(',', AppSetting::get(AppSetting::KEY_CUSTOMER_REMIND_CHANNELS, 'whatsapp,sms,email'))
        );

        $chqDate = $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—';
        $ref = $payment->reference_number ?: '—';
        $amount = number_format((float) $payment->amount, 2);
        $orderNo = $order->order_number;
        $appName = config('app.name');

        // Default message — used when no custom message is provided
        $defaultMsg = implode("\n", [
            "*{$appName}* — Cheque payment reminder",
            "Dear *{$customer->name}*,",
            '',
            "This is a reminder regarding your cheque payment for order *{$orderNo}*.",
            "Cheque date: *{$chqDate}*",
            "Reference / cheque no.: *{$ref}*",
            "Amount: Rs {$amount}",
            '',
            'Please ensure the cheque is ready to be presented on the above date.',
            'Thank you.',
        ]);

        $body = $customMessage ? trim($customMessage) : $defaultMsg;

        // WhatsApp to customer
        if (in_array('whatsapp', $channels, true) && !empty(trim($customer->phone ?? ''))) {
            try {
                $results['whatsapp'] = $this->whatsapp->sendText($customer->phone, $body);
            } catch (\Throwable $e) {
                Log::warning('Customer reminder WhatsApp failed: '.$e->getMessage());
                $results['whatsapp'] = false;
            }
        }

        // SMS to customer
        if (in_array('sms', $channels, true) && !empty(trim($customer->phone ?? ''))) {
            $smsBody = $customMessage
                ? strip_tags($customMessage)
                : "{$appName}: Reminder — cheque for order {$orderNo}. Cheque date: {$chqDate}. Ref: {$ref}. Amount: Rs {$amount}. Please ensure it is ready. Thank you.";
            try {
                $results['sms'] = $this->sms->sendSms($customer->phone, $smsBody);
            } catch (\Throwable $e) {
                Log::warning('Customer reminder SMS failed: '.$e->getMessage());
                $results['sms'] = false;
            }
        }

        // Email to customer
        if (in_array('email', $channels, true) && !empty(trim($customer->email ?? ''))) {
            try {
                Mail::to($customer->email)->send(new \App\Mail\ChequeReminderCustomerMail($payment, $order, $customer, $body));
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::warning('Customer reminder email failed: '.$e->getMessage());
                $results['email'] = false;
            }
        }

        return $results;
    }

    /**
     * Cheque bounced/returned — notify admin, sales person, and customer.
     */
    public function notifyChequeBounced(Payment $payment, string $reason): void
    {
        $payment->loadMissing(['order.customer', 'order.salesPerson.employee']);
        $order = $payment->order;
        if (!$order) {
            return;
        }

        $customer = $order->customer;
        $salesUser = $order->salesPerson;
        $photoUrl = $this->signedChequePhotoUrl($payment);

        $intro = '*ALERT: Cheque RETURNED / BOUNCED*';
        $body = $this->buildBounceWhatsapp($payment, $order, $customer, $salesUser, $reason, $intro);
        if ($photoUrl) {
            $body = "*Cheque copy attached.*\n\n".$body;
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_ADMIN_WA, true)) {
            $adminPhone = AppSetting::get(AppSetting::KEY_HOSTGRAP_ADMIN_PHONE) ?: config('services.hostgrap.admin_phone');
            if ($adminPhone) {
                $this->pushChequeWhatsapp($adminPhone, $photoUrl, $body);
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_SALES_WA, true) && $salesUser) {
            $spPhone = $salesUser->employee?->contact;
            if ($spPhone) {
                $this->pushChequeWhatsapp($spPhone, $photoUrl, $body);
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_CUSTOMER_SMS, true) && $customer && !empty(trim($customer->phone ?? ''))) {
            $sms = config('app.name').': Your cheque for order '.$order->order_number
                .' (Rs '.number_format((float) $payment->amount, 2).') was RETURNED. Reason: '.$reason
                .'. Please contact us to arrange another payment. Thank you.';
            try {
                $this->sms->sendSms($customer->phone, $sms);
            } catch (\Throwable $e) {
                Log::warning('Cheque bounce customer SMS failed: '.$e->getMessage());
            }
        }

        if (AppSetting::getBool(AppSetting::KEY_NOTIFY_CUSTOMER_EMAIL, true) && $customer && !empty(trim($customer->email ?? ''))) {
            try {
                Mail::to($customer->email)->send(new \App\Mail\ChequeBouncedCustomerMail($payment, $order, $customer, $reason));
            } catch (\Throwable $e) {
                Log::warning('Cheque bounce customer email failed: '.$e->getMessage());
            }
        }
    }

    protected function buildBounceWhatsapp(
        Payment $payment,
        $order,
        $customer,
        $salesUser,
        string $reason,
        string $intro,
    ): string {
        $lines = [
            '*'.config('app.name').' — Cheque bounced*',
            $intro,
            '',
            'Order: *'.$order->order_number.'*',
            'Amount: Rs '.number_format((float) $payment->amount, 2),
            'Cheque date: '.($payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—'),
            'Reference: '.($payment->reference_number ?: '—'),
            'Reason: '.$reason,
            '',
            'Invoice is *unpaid again*. Contact customer for cash/bank transfer.',
            '',
            'Sales person: '.($salesUser ? $salesUser->name : '—'),
            'Customer: '.($customer ? $customer->name : '—'),
        ];

        if ($customer && !empty(trim($customer->phone ?? ''))) {
            $lines[] = 'Customer phone: '.$customer->phone;
        }

        return implode("\n", $lines);
    }

    /**
     * Send cheque scan via HostGrap send-image when URL is available; otherwise text only.
     * On send-image failure: text only (no link — user preference).
     */
    protected function pushChequeWhatsapp(string $phone, ?string $publicImageUrl, string $body): void
    {
        $body = trim($body);
        if ($publicImageUrl) {
            $caption = Str::limit($body, self::MAX_IMAGE_CAPTION, '…');
            if ($this->whatsapp->sendImage($phone, $publicImageUrl, $caption)) {
                return;
            }
            Log::warning('HostGrap send-image failed; sending WhatsApp text only (no image link).', [
                'phone' => $phone,
            ]);
        }

        $this->whatsapp->sendText($phone, Str::limit($body, 4096, ''));
    }

    /**
     * Returns a publicly reachable URL for the cheque image.
     *
     * HostGrap's server must be able to GET this URL (i.e. it must NOT be http://localhost).
     * In production set APP_URL to your real HTTPS domain.
     * On localhost the URL is not reachable → we skip the image and send text only.
     */
    protected function signedChequePhotoUrl(Payment $payment): ?string
    {
        if (!$payment->cheque_image_path) {
            return null;
        }

        $appUrl = rtrim(config('app.url', ''), '/');

        // Detect non-routable base URL (local dev) — HostGrap can't reach these
        $host = parse_url($appUrl, PHP_URL_HOST) ?? '';
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
        ) {
            Log::info('ChequePaymentNotifier: APP_URL is '.$appUrl.' (non-public). Skipping image; WhatsApp text will be sent instead.');

            return null;
        }

        try {
            return URL::temporarySignedRoute(
                'order-payments.cheque-image-signed',
                now()->addDays(30),
                ['payment' => $payment->id]
            );
        } catch (\Throwable $e) {
            Log::warning('Could not build signed cheque image URL: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Admin: full context — whose customer, whose sale, ref, dates (no URL; image sent separately).
     */
    protected function buildAdminNewChequeWhatsapp(
        Payment $payment,
        $order,
        $customer,
        $salesUser,
    ): string {
        $lines = [
            '*'.config('app.name').' — ADMIN*',
            '*Cheque payment recorded* (චෙක් ගෙවීමක් රෙකෝඩ් වෙලා.)',
            'A customer paid by *cheque*. Summary:',
            '',
            '*Order:* '.$order->order_number,
            '*Amount:* Rs '.number_format((float) $payment->amount, 2),
            '*Payment date:* '.$payment->payment_date->format('Y-m-d'),
            '*Cheque date (on cheque):* '.($payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—'),
            '*Reference / cheque no.:* '.($payment->reference_number ?: '—'),
            '',
        ];

        $lines[] = '*Sales person (order owner):* '.($salesUser ? $salesUser->name : '—');
        if ($salesUser) {
            $spPhone = trim((string) ($salesUser->employee?->contact ?? ''));
            if ($spPhone !== '') {
                $lines[] = 'Sales person phone (employee): '.$spPhone;
            }
            $em = trim((string) ($salesUser->email ?? ''));
            if ($em !== '') {
                $lines[] = 'Sales person email: '.$em;
            }
        }

        $lines[] = '';
        $lines[] = '*Customer:* '.($customer ? $customer->name : '—');
        if ($customer) {
            if (!empty(trim($customer->phone ?? ''))) {
                $lines[] = 'Customer phone: '.$customer->phone;
            }
            if (!empty(trim($customer->email ?? ''))) {
                $lines[] = 'Customer email: '.$customer->email;
            }
            if (!empty(trim($customer->address ?? ''))) {
                $lines[] = 'Customer address: '.$customer->address;
            }
        }

        if (!$payment->cheque_image_path) {
            $lines[] = '';
            $lines[] = 'Cheque photo: not uploaded.';
        }

        return implode("\n", $lines);
    }

    /**
     * Sales person: shorter recap (image sent separately).
     */
    protected function buildSalesNewChequeWhatsapp(
        Payment $payment,
        $order,
        $customer,
    ): string {
        $lines = [
            '*'.config('app.name').' — Cheque payment*',
            'Order *'.$order->order_number.'* — cheque recorded.',
            'Amount: Rs '.number_format((float) $payment->amount, 2),
            'Cheque date: '.($payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—'),
            'Reference: '.($payment->reference_number ?: '—'),
            'Customer: '.($customer ? $customer->name : '—'),
        ];

        if ($customer && !empty(trim($customer->phone ?? ''))) {
            $lines[] = 'Customer phone: '.$customer->phone;
        }

        if (!$payment->cheque_image_path) {
            $lines[] = '';
            $lines[] = 'Cheque photo: not uploaded.';
        }

        return implode("\n", $lines);
    }

    protected function buildReminderWhatsapp(
        Payment $payment,
        $order,
        $customer,
        $salesUser,
        string $intro,
    ): string {
        $lines = [
            '*'.config('app.name').' — Cheque reminder*',
            $intro,
            '',
            'Order: '.$order->order_number,
            'Amount: Rs '.number_format((float) $payment->amount, 2),
            'Cheque date: '.($payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—'),
            'Reference: '.($payment->reference_number ?: '—'),
            'Sales person: '.($salesUser ? $salesUser->name : '—'),
            'Customer: '.($customer ? $customer->name : '—'),
        ];

        if (!$payment->cheque_image_path) {
            $lines[] = '';
            $lines[] = 'Cheque photo: not uploaded.';
        }

        return implode("\n", $lines);
    }

    protected function buildCustomerSms(Payment $payment, $order, $customer): string
    {
        $ref = $payment->reference_number ?: '—';
        $chqDate = $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—';

        return config('app.name').': Your cheque payment for order '.$order->order_number
            .' has been recorded. Cheque date: '.$chqDate.'. Reference: '.$ref
            .'. Amount: Rs '.number_format((float) $payment->amount, 2).'. Thank you.';
    }
}
