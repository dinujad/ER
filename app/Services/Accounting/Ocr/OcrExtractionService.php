<?php

namespace App\Services\Accounting\Ocr;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Intelligent document capture — draft only, never auto-posts journals.
 */
class OcrExtractionService
{
    /**
     * @return array{document_id:int, status:string}
     */
    public function upload(int $businessId, ?int $userId, UploadedFile $file, string $documentType = 'supplier_invoice'): array
    {
        $cfg = config('accounting_ai.ocr');
        if (! ($cfg['enabled'] ?? true)) {
            throw new RuntimeException('OCR is disabled.');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        if (! in_array($ext, $cfg['allowed_extensions'] ?? [], true)
            && ! in_array($mime, $cfg['allowed_mimes'] ?? [], true)) {
            throw new RuntimeException('Unsupported file format. Use JPG, PNG, or PDF.');
        }

        $maxKb = (int) ($cfg['max_file_kb'] ?? 10240);
        if ($file->getSize() > $maxKb * 1024) {
            throw new RuntimeException('File exceeds maximum size.');
        }

        $disk = $cfg['disk'] ?? 'local';
        $path = ($cfg['path_prefix'] ?? 'accounting/ocr').'/'.$businessId.'/'.Str::uuid().'.'.$ext;
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        $fingerprint = hash_file('sha256', $file->getRealPath()) ?: null;

        $id = (int) DB::table('acct_ocr_documents')->insertGetId([
            'business_id' => $businessId,
            'uploaded_by' => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => (int) $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $path,
            'document_type' => $documentType,
            'status' => 'queued',
            'fingerprint' => $fingerprint,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['document_id' => $id, 'status' => 'queued'];
    }

    /**
     * Process extraction synchronously (tests) or via job.
     *
     * @param  array<string, mixed>|null  $forcedFields  test double payload
     * @return array<string, mixed>
     */
    public function process(int $documentId, ?array $forcedFields = null, ?string $pastedText = null): array
    {
        $doc = DB::table('acct_ocr_documents')->where('id', $documentId)->first();
        if (! $doc) {
            throw new RuntimeException('OCR document not found.');
        }

        DB::table('acct_ocr_documents')->where('id', $documentId)->update([
            'status' => 'processing',
            'updated_at' => now(),
        ]);

        try {
            if ($forcedFields === null && $pastedText !== null && trim($pastedText) !== '') {
                // Reviewer pasted / typed the invoice text (scanned PDF, no OCR engine on the server).
                $forcedFields = app(InvoiceTextParser::class)->parse($pastedText) + ['extract_method' => 'pasted'];
            }
            $fields = $forcedFields ?? $this->extractFields($doc);
            $normalized = $this->normalizeFields($fields);
            $validation = $this->validateAmounts($normalized);
            $duplicate = $this->detectDuplicate((int) $doc->business_id, $normalized, (string) ($doc->fingerprint ?? ''), $documentId);

            $confidence = (float) ($normalized['confidence'] ?? ($forcedFields ? 90 : 0));
            if ($forcedFields === null && ($this->driver() === 'fake')) {
                // Without a real OCR model, do not invent values
                if (empty($forcedFields) && empty($fields['invoice_number']) && empty($fields['total'])) {
                    $normalized = array_merge($normalized, [
                        'invoice_number' => null,
                        'total' => null,
                        'subtotal' => null,
                        'tax_amount' => null,
                        'supplier_name' => null,
                    ]);
                    $confidence = 0;
                    $validation['errors'][] = 'OCR model unavailable — no values invented. Enter fields manually.';
                    $validation['valid'] = false;
                }
            }

            $extractionId = (int) DB::table('acct_ocr_extractions')->insertGetId([
                'ocr_document_id' => $documentId,
                'business_id' => $doc->business_id,
                'raw_text' => json_encode(['text' => $fields['raw_text'] ?? null, 'method' => $fields['extract_method'] ?? null]),
                'extracted_fields' => json_encode($normalized),
                'line_items' => json_encode($normalized['line_items'] ?? []),
                'validation_errors' => json_encode($validation['errors']),
                'confidence' => $confidence,
                'currency_flag' => $normalized['currency'] ?? 'LKR',
                'currency_supported' => (bool) ($normalized['currency_supported'] ?? true),
                'amounts_valid' => (bool) $validation['valid'],
                'duplicate_suspected' => (bool) $duplicate['suspected'],
                'duplicate_reason' => $duplicate['reason'],
                'extractor' => $this->driver(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('acct_ocr_documents')->where('id', $documentId)->update([
                'status' => 'extracted',
                'updated_at' => now(),
            ]);

            $this->createReviewTask((int) $doc->business_id, $documentId, $normalized, $confidence);

            return [
                'document_id' => $documentId,
                'extraction_id' => $extractionId,
                'fields' => $normalized,
                'validation' => $validation,
                'duplicate' => $duplicate,
                'status' => 'extracted',
                'posted' => false,
            ];
        } catch (\Throwable $e) {
            DB::table('acct_ocr_documents')->where('id', $documentId)->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'retry_count' => DB::raw('retry_count + 1'),
                'updated_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a draft purchase candidate — never posts to GL.
     *
     * @param  array<string, mixed>  $editedFields
     * @return array{draft_type:string, draft_id:?int, posted:bool}
     */
    public function createDraft(int $documentId, int $businessId, array $editedFields, ?int $userId = null): array
    {
        $doc = DB::table('acct_ocr_documents')
            ->where('id', $documentId)
            ->where('business_id', $businessId)
            ->first();
        if (! $doc) {
            throw new RuntimeException('Document not found.');
        }

        $extraction = DB::table('acct_ocr_extractions')
            ->where('ocr_document_id', $documentId)
            ->orderByDesc('id')
            ->first();

        $fields = array_merge(
            $extraction ? (json_decode($extraction->extracted_fields ?? '{}', true) ?: []) : [],
            $editedFields
        );

        if (($fields['currency'] ?? 'LKR') !== 'LKR' || ($fields['currency_supported'] ?? true) === false) {
            throw new RuntimeException('Foreign-currency documents cannot be drafted under LKR-only policy.');
        }

        $validation = $this->validateAmounts($fields);
        if (! $validation['valid']) {
            throw new RuntimeException('Amount validation failed: '.implode('; ', $validation['errors']));
        }

        $draftId = null;
        $draftType = 'ocr_review_candidate';

        // Prefer linking as review candidate; only create purchase row if purchases table exists AND user confirmed
        if (! empty($fields['create_purchase_draft']) && Schema::hasTable('purchases')) {
            $supplierId = (int) ($fields['supplier_id'] ?? 0);
            if ($supplierId <= 0 || ! DB::table('suppliers')->where('id', $supplierId)->exists()) {
                throw new RuntimeException('Choose the supplier before creating the draft bill.');
            }
            if (! empty($fields['invoice_number']) && DB::table('purchases')->where('supplier_id', $supplierId)
                ->where('reference_no', $fields['invoice_number'])->where('status', '!=', 'cancelled')->exists()) {
                throw new RuntimeException('A bill with supplier invoice no. '.$fields['invoice_number'].' already exists for this supplier.');
            }
            $total = round((float) ($fields['total'] ?? 0), 2);
            $tax = round((float) ($fields['tax_amount'] ?? 0), 2);
            $supplierVat = DB::table('suppliers')->where('id', $supplierId)->value('tax_vat_no');

            \App\Models\Purchase::lockPurchaseNumberGeneration();
            $purchase = \App\Models\Purchase::query()->create([
                'purchase_no' => \App\Models\Purchase::generatePurchaseNo(),
                'date' => $fields['invoice_date'] ?? date('Y-m-d'),
                'supplier_id' => $supplierId,
                'purchase_type' => \App\Models\Purchase::TYPE_CREDIT,
                'reference_no' => $fields['invoice_number'] ?? ('OCR-'.$documentId),
                'notes' => 'Draft from scanned supplier invoice (OCR document #'.$documentId.'). Add the items, check and confirm.',
                'status' => \App\Models\Purchase::STATUS_DRAFT,
                'subtotal' => round($total - $tax, 2),
                'discount_amount' => round((float) ($fields['discount'] ?? 0), 2),
                'tax_amount' => $tax,
                'shipping_cost' => 0,
                'total' => $total,
                'paid_amount' => 0,
                'input_vat_claimable' => $tax > 0 && filled($supplierVat),
                'created_by' => $userId,
            ]);
            $draftId = (int) $purchase->id;
            $draftType = 'purchase';
        }

        DB::table('acct_ocr_documents')->where('id', $documentId)->update([
            'status' => 'draft_created',
            'draft_source_type' => $draftType,
            'draft_source_id' => $draftId,
            'updated_at' => now(),
        ]);

        return [
            'draft_type' => $draftType,
            'draft_id' => $draftId,
            'posted' => false,
            'fields' => $fields,
        ];
    }

    /**
     * @param  object  $doc
     * @return array<string, mixed>
     */
    protected function extractFields(object $doc): array
    {
        // 'fake' driver (tests / demo): never invents values.
        if ($this->driver() === 'fake') {
            return ['raw_text' => null, 'confidence' => 0];
        }

        try {
            $path = Storage::disk($doc->storage_disk ?: 'local')->path($doc->storage_path);
        } catch (\Throwable) {
            return ['raw_text' => null, 'confidence' => 0, 'extract_method' => 'none'];
        }
        $read = app(DocumentTextExtractor::class)->extract($path, (string) $doc->mime_type);
        if ($read['text'] === null) {
            return ['raw_text' => null, 'confidence' => 0, 'extract_method' => 'none'];
        }

        return app(InvoiceTextParser::class)->parse($read['text']) + ['extract_method' => $read['method']];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function normalizeFields(array $fields): array
    {
        $currency = strtoupper(trim((string) ($fields['currency'] ?? 'LKR')));
        $supported = $currency === '' || $currency === 'LKR' || $currency === 'RS' || $currency === 'රු';

        return [
            'document_type' => $fields['document_type'] ?? 'supplier_invoice',
            'supplier_name' => $fields['supplier_name'] ?? null,
            'supplier_id' => $fields['supplier_id'] ?? null,
            'invoice_number' => $fields['invoice_number'] ?? null,
            'invoice_date' => $fields['invoice_date'] ?? null,
            'due_date' => $fields['due_date'] ?? null,
            'currency' => $supported ? 'LKR' : $currency,
            'currency_supported' => $supported,
            'subtotal' => isset($fields['subtotal']) ? round((float) $fields['subtotal'], 2) : null,
            'tax_amount' => isset($fields['tax_amount']) ? round((float) $fields['tax_amount'], 2) : null,
            'discount' => isset($fields['discount']) ? round((float) $fields['discount'], 2) : 0.0,
            'total' => isset($fields['total']) ? round((float) $fields['total'], 2) : null,
            'line_items' => $fields['line_items'] ?? [],
            'payment_info' => $fields['payment_info'] ?? null,
            'confidence' => isset($fields['confidence']) ? (float) $fields['confidence'] : null,
            'tax_inclusive' => (bool) ($fields['tax_inclusive'] ?? false),
            'supplier_vat_no' => $fields['supplier_vat_no'] ?? null,
            'sscl_amount' => isset($fields['sscl_amount']) ? round((float) $fields['sscl_amount'], 2) : null,
            'field_confidence' => $fields['field_confidence'] ?? [],
            'extract_method' => $fields['extract_method'] ?? null,
        ];
    }

    /**
     * Subtotal − Discount + Tax = Total (tax-exclusive convention).
     * Tax-inclusive documents validated separately.
     *
     * @param  array<string, mixed>  $fields
     * @return array{valid:bool, errors:list<string>, expected_total:?float}
     */
    public function validateAmounts(array $fields): array
    {
        $errors = [];
        if (empty($fields['invoice_number'])) {
            $errors[] = 'Missing invoice number';
        }
        if (($fields['currency_supported'] ?? true) === false) {
            $errors[] = 'Unsupported currency for LKR-only posting';
        }

        $subtotal = $fields['subtotal'] ?? null;
        $tax = $fields['tax_amount'] ?? 0.0;
        $discount = $fields['discount'] ?? 0.0;
        $total = $fields['total'] ?? null;
        $expected = null;

        if ($subtotal !== null && $total !== null) {
            if (! empty($fields['tax_inclusive'])) {
                $expected = round((float) $subtotal - (float) $discount, 2);
            } else {
                $expected = round((float) $subtotal - (float) $discount + (float) $tax, 2);
            }
            if (abs($expected - (float) $total) > 0.02) {
                $errors[] = sprintf(
                    'Amount mismatch: expected total LKR %s, got LKR %s',
                    number_format($expected, 2, '.', ''),
                    number_format((float) $total, 2, '.', '')
                );
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'expected_total' => $expected,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array{suspected:bool, reason:?string}
     */
    public function detectDuplicate(int $businessId, array $fields, string $fingerprint, int $exceptDocId): array
    {
        if ($fingerprint !== '' && Schema::hasTable('acct_ocr_documents')) {
            $dup = DB::table('acct_ocr_documents')
                ->where('business_id', $businessId)
                ->where('fingerprint', $fingerprint)
                ->where('id', '!=', $exceptDocId)
                ->exists();
            if ($dup) {
                return ['suspected' => true, 'reason' => 'Identical document fingerprint'];
            }
        }

        $invoice = $fields['invoice_number'] ?? null;
        $supplier = $fields['supplier_name'] ?? null;
        $total = $fields['total'] ?? null;
        if ($invoice && Schema::hasTable('acct_ocr_extractions')) {
            $rows = DB::table('acct_ocr_extractions')
                ->where('business_id', $businessId)
                ->where('ocr_document_id', '!=', $exceptDocId)
                ->orderByDesc('id')
                ->limit(100)
                ->get(['extracted_fields']);
            foreach ($rows as $row) {
                $f = json_decode($row->extracted_fields ?? '{}', true) ?: [];
                if (($f['invoice_number'] ?? null) === $invoice
                    && ($supplier === null || ($f['supplier_name'] ?? null) === $supplier)
                    && ($total === null || abs((float) ($f['total'] ?? 0) - (float) $total) < 0.009)) {
                    return ['suspected' => true, 'reason' => 'Same supplier invoice number and amount'];
                }
            }
        }

        return ['suspected' => false, 'reason' => null];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    protected function createReviewTask(int $businessId, int $documentId, array $fields, float $confidence): void
    {
        if (! Schema::hasTable('acct_ai_review_tasks')) {
            return;
        }
        $exists = DB::table('acct_ai_review_tasks')
            ->where('business_id', $businessId)
            ->where('subject_type', 'ocr_document')
            ->where('subject_id', $documentId)
            ->where('task_type', 'ocr_extraction')
            ->exists();
        if ($exists) {
            return;
        }
        DB::table('acct_ai_review_tasks')->insert([
            'business_id' => $businessId,
            'task_type' => 'ocr_extraction',
            'subject_type' => 'ocr_document',
            'subject_id' => $documentId,
            'title' => 'Review OCR: '.($fields['invoice_number'] ?? ('document #'.$documentId)),
            'suggestion_payload' => json_encode($fields),
            'confidence' => $confidence,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function driver(): string
    {
        return (string) config('accounting_ai.ocr.driver', 'fake');
    }
}
