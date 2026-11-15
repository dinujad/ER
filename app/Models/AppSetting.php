<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AppSetting extends Model
{
    public const KEY_HOSTGRAP_EMAIL = 'cheque_notify.hostgrap_email';

    public const KEY_HOSTGRAP_API_KEY_ENC = 'cheque_notify.hostgrap_api_key_enc';

    public const KEY_HOSTGRAP_ADMIN_PHONE = 'cheque_notify.hostgrap_admin_phone';

    public const KEY_NOTIFY_CUSTOMER_SMS = 'cheque_notify.customer_sms';

    public const KEY_NOTIFY_CUSTOMER_EMAIL = 'cheque_notify.customer_email';

    public const KEY_NOTIFY_ADMIN_WA = 'cheque_notify.admin_whatsapp';

    public const KEY_NOTIFY_SALES_WA = 'cheque_notify.sales_whatsapp';

    /** How many days before the cheque date to send the automatic customer reminder (default 3). */
    public const KEY_CUSTOMER_REMIND_DAYS_BEFORE = 'cheque_notify.customer_remind_days_before';

    /** Which channels to use for the auto customer reminder (comma-separated: whatsapp,sms,email) */
    public const KEY_CUSTOMER_REMIND_CHANNELS = 'cheque_notify.customer_remind_channels';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function getBool(string $key, bool $default = true): bool
    {
        $v = static::get($key);

        if ($v === null) {
            return $default;
        }

        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getDecryptedApiKey(): ?string
    {
        $enc = static::get(self::KEY_HOSTGRAP_API_KEY_ENC);
        if ($enc === null || $enc === '') {
            return config('services.hostgrap.api_key');
        }
        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function putEncryptedApiKey(?string $plain): void
    {
        if ($plain === null || trim($plain) === '') {
            return;
        }
        static::put(self::KEY_HOSTGRAP_API_KEY_ENC, Crypt::encryptString(trim($plain)));
    }
}
