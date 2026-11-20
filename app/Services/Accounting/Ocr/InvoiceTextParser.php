<?php

namespace App\Services\Accounting\Ocr;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — reads supplier invoice fields out of plain text (Sri Lankan tax invoice layouts:
 * "Tax Invoice No", "Supplier's TIN", "VAT 18%", "Total Amount including VAT" …).
 * Every value it is not sure about is left empty for the reviewer — nothing is invented.
 */
class InvoiceTextParser
{
    /**
     * @return array<string, mixed> fields + per-field confidence (0–100) + overall confidence
     */
    public function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r", "\t", "\u{00A0}"], ["\n", "\n", ' ', ' '], $text);
        $lines = array_values(array_filter(array_map(fn ($l) => trim(preg_replace('/ {2,}/', '  ', $l)), explode("\n", $text)), fn ($l) => $l !== ''));
        $flat = implode("\n", $lines);
        $conf = [];

        $invoiceNo = $this->firstMatch($flat, [
            '/(?:tax\s+)?invoice\s*(?:no\.?|number|#)\s*[:\-]?\s*([A-Z0-9][A-Z0-9\-\/]{2,30})/i',
            '/\binv(?:oice)?\s*[:#]\s*([A-Z0-9][A-Z0-9\-\/]{2,30})/i',
            '/\bbill\s*(?:no\.?|number)\s*[:\-]?\s*([A-Z0-9][A-Z0-9\-\/]{2,30})/i',
        ]);
        $conf['invoice_number'] = $invoiceNo ? 90 : 0;

        $invoiceDate = $this->date($this->firstMatch($flat, [
            '/(?:date\s+of\s+invoice|invoice\s+date|tax\s+invoice\s+date|bill\s+date)\s*[:\-]?\s*([0-9]{1,4}[\/\-.][0-9]{1,2}[\/\-.][0-9]{1,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4})/i',
            '/\bdate\s*[:\-]?\s*([0-9]{1,4}[\/\-.][0-9]{1,2}[\/\-.][0-9]{1,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4})/i',
        ]));
        $conf['invoice_date'] = $invoiceDate ? 85 : 0;
        $dueDate = $this->date($this->firstMatch($flat, ['/(?:due\s+date|payment\s+due)\s*[:\-]?\s*([0-9]{1,4}[\/\-.][0-9]{1,2}[\/\-.][0-9]{1,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{4})/i']));

        $vatNo = $this->firstMatch($flat, [
            '/supplier\'?s?\s+(?:tin|vat\s*(?:reg\.?|registration)?\s*no\.?)\s*[:\-]?\s*([0-9]{9}(?:-?[0-9]{4})?)/i',
            '/\bvat\s*(?:reg\.?|registration)?\s*(?:no\.?|number)\s*[:\-]?\s*([0-9]{9}(?:-?[0-9]{4})?)/i',
            '/\btin\s*[:\-]?\s*([0-9]{9})\b/i',
        ]);

        $total = $this->amount($this->lastMatch($flat, [
            '/total\s+amount[^\n0-9]*including\s+vat[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
            '/(?:grand\s+total|total\s+(?:amount\s+)?payable|amount\s+due|net\s+total|invoice\s+total|total\s+amount)[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
            '/\btotal\b[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
        ]));
        $tax = $this->amount($this->lastMatch($flat, [
            '/vat\s+amount[^\n0-9]*?(?:@\s*[0-9.]+\s*%)?[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
            '/\bvat\b\s*(?:@?\s*[0-9.]+\s*%)?[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
            '/\btax\b\s*(?:@?\s*[0-9.]+\s*%)?[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
        ]));
        $sscl = $this->amount($this->lastMatch($flat, ['/\bsscl\b[^\n0-9]*?(?:[0-9.]+\s*%)?[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i']));
        $subtotal = $this->amount($this->lastMatch($flat, [
            '/total\s+value\s+of\s+supply[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
            '/(?:sub\s*-?\s*total|amount\s+excluding\s+vat|total\s+excluding\s+vat|net\s+amount)[^\n0-9]*?([0-9][0-9,]*\.[0-9]{2})/i',
        ]));

        // Make the three amounts agree where two are known.
        if ($total !== null && $tax !== null && $subtotal === null) {
            $subtotal = round($total - $tax, 2);
            $conf['subtotal'] = 60;
        }
        if ($total !== null && $subtotal !== null && $tax === null && $total > $subtotal) {
            $tax = round($total - $subtotal, 2);
            $conf['tax_amount'] = 60;
        }
        if ($total === null && $subtotal !== null) {
            $total = round($subtotal + (float) $tax, 2);
            $conf['total'] = 60;
        }
        $conf['total'] ??= $total !== null ? 85 : 0;
        $conf['tax_amount'] ??= $tax !== null ? 80 : 0;
        $conf['subtotal'] ??= $subtotal !== null ? 80 : 0;
        $consistent = $total !== null && $subtotal !== null && abs($subtotal + (float) $tax - $total) <= 0.05;
        if ($consistent) {
            $conf['total'] = max($conf['total'], 95);
            $conf['subtotal'] = max($conf['subtotal'], 90);
        }

        [$supplierId, $supplierName, $supplierConf] = $this->supplier($vatNo, $lines);
        $conf['supplier'] = $supplierConf;

        $overall = (int) round(array_sum([$conf['invoice_number'], $conf['invoice_date'], $conf['total'], $conf['supplier']]) / 4);

        return [
            'document_type' => 'supplier_invoice',
            'supplier_name' => $supplierName,
            'supplier_id' => $supplierId,
            'supplier_vat_no' => $vatNo,
            'invoice_number' => $invoiceNo,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'currency' => preg_match('/\b(USD|EUR|GBP|INR)\b|\$/', $flat) && !preg_match('/\b(LKR|Rs\.?)\b/i', $flat) ? 'USD' : 'LKR',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'sscl_amount' => $sscl,
            'discount' => 0.0,
            'total' => $total,
            'tax_inclusive' => false,
            'confidence' => $overall,
            'field_confidence' => $conf,
            'amounts_consistent' => $consistent,
            'raw_text' => mb_substr($text, 0, 20000),
        ];
    }

    /** @return array{0: ?int, 1: ?string, 2: int} */
    private function supplier(?string $vatNo, array $lines): array
    {
        if (Schema::hasTable('suppliers')) {
            if ($vatNo) {
                $digits = preg_replace('/\D/', '', $vatNo);
                foreach (DB::table('suppliers')->whereNotNull('tax_vat_no')->get(['id', 'name', 'company_name', 'tax_vat_no']) as $s) {
                    $sd = preg_replace('/\D/', '', (string) $s->tax_vat_no);
                    if ($sd !== '' && (str_starts_with($sd, substr($digits, 0, 9)) || str_starts_with($digits, substr($sd, 0, 9)))) {
                        return [(int) $s->id, $s->company_name ?: $s->name, 98];
                    }
                }
            }
            $head = mb_strtolower(implode(' ', array_slice($lines, 0, 12)));
            foreach (DB::table('suppliers')->get(['id', 'name', 'company_name']) as $s) {
                foreach (array_filter([$s->company_name, $s->name]) as $n) {
                    if (mb_strlen($n) >= 4 && str_contains($head, mb_strtolower($n))) {
                        return [(int) $s->id, $s->company_name ?: $s->name, 85];
                    }
                }
            }
        }
        // Unknown supplier: first line that looks like a company name.
        foreach (array_slice($lines, 0, 6) as $l) {
            if (preg_match('/\b(PVT|LTD|LIMITED|PLC|\(PRIVATE\)|COMPANY|TRADERS|ENTERPRISES|STORES|MILLS)\b/i', $l) && mb_strlen($l) <= 80) {
                return [null, trim($l), 50];
            }
        }

        return [null, null, 0];
    }

    private function firstMatch(string $text, array $patterns): ?string
    {
        foreach ($patterns as $p) {
            if (preg_match($p, $text, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    private function lastMatch(string $text, array $patterns): ?string
    {
        foreach ($patterns as $p) {
            if (preg_match_all($p, $text, $m) && $m[1]) {
                return trim(end($m[1]));
            }
        }

        return null;
    }

    private function amount(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $v = (float) str_replace(',', '', $raw);

        return $v > 0 ? round($v, 2) : null;
    }

    private function date(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        // Sri Lankan invoices are day-first; month-first is tried only when day-first is impossible.
        $formats = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d', 'd/m/y', 'd-m-y', 'j M Y', 'j F Y', 'd M Y', 'd F Y', 'm/d/Y'];
        foreach ($formats as $f) {
            $d = \DateTime::createFromFormat('!' . $f, $raw);
            $err = \DateTime::getLastErrors();
            if ($d && (!$err || ($err['warning_count'] === 0 && $err['error_count'] === 0))) {
                $y = (int) $d->format('Y');
                if ($y > 2000 && $y < 2100) {
                    return $d->format('Y-m-d');
                }
            }
        }

        return null;
    }
}
