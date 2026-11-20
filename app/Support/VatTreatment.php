<?php

namespace App\Support;

/**
 * Phase 6.3 — VAT treatment of a sales invoice (Xero "tax rate" on the invoice).
 * SSCL is charged on every treatment (it is a levy on turnover); only VAT changes.
 */
class VatTreatment
{
    public const STANDARD = 'standard';
    public const ZERO_RATED = 'zero';
    public const EXEMPT = 'exempt';

    public static function options(): array
    {
        return [
            self::STANDARD => 'Standard rated — VAT ' . self::rateLabel(),
            self::ZERO_RATED => 'Zero rated (0%) — exports / zero-rated supplies',
            self::EXEMPT => 'Exempt supply — no VAT',
        ];
    }

    public static function help(): array
    {
        return [
            self::STANDARD => 'Normal local sale. SSCL and VAT are both charged.',
            self::ZERO_RATED => 'VAT is 0% but the sale is still reported in the VAT return as a zero-rated supply. SSCL is still charged.',
            self::EXEMPT => 'Goods or customer exempt from VAT. No VAT is charged; the sale is reported as exempt. SSCL is still charged.',
        ];
    }

    public static function normalize(?string $code): string
    {
        return array_key_exists((string) $code, self::options()) ? (string) $code : self::STANDARD;
    }

    public static function vatRate(?string $code): float
    {
        return self::normalize($code) === self::STANDARD
            ? max(0, (float) config('dermas.vat_rate', 18))
            : 0.0;
    }

    public static function label(?string $code): string
    {
        return match (self::normalize($code)) {
            self::ZERO_RATED => 'Zero rated',
            self::EXEMPT => 'Exempt',
            default => 'Standard rated',
        };
    }

    private static function rateLabel(): string
    {
        return rtrim(rtrim(number_format((float) config('dermas.vat_rate', 18), 2, '.', ''), '0'), '.') . '%';
    }
}
