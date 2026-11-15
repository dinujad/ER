<?php

namespace App\Services;

use App\Models\WashingTransfer;
use Picqer\Barcode\BarcodeGeneratorPNG;

class WashingGatePassService
{
    public function queueNumber(WashingTransfer $transfer): string
    {
        return $transfer->batch_no ?: ('Q' . str_pad((string) $transfer->id, 4, '0', STR_PAD_LEFT));
    }

    public function jobNumber(WashingTransfer $transfer): string
    {
        return $transfer->job?->job_number ?? 'N/A';
    }

    public function barcodeValue(WashingTransfer $transfer): string
    {
        $jobNo = $this->jobNumber($transfer);
        $queueNo = $this->queueNumber($transfer);

        return 'WGP-' . $jobNo . '-' . $queueNo . '-' . $transfer->id;
    }

    public function barcodePngBase64(string $value): string
    {
        $generator = new BarcodeGeneratorPNG();

        return base64_encode($generator->getBarcode($value, $generator::TYPE_CODE_128, 2, 70));
    }

    /** @return array<string, mixed> */
    public function printPayload(WashingTransfer $transfer): array
    {
        $transfer->loadMissing('job.product');
        $jobNo = $this->jobNumber($transfer);
        $queueNo = $this->queueNumber($transfer);
        $barcodeValue = $this->barcodeValue($transfer);
        $barcodePng = $this->barcodePngBase64($barcodeValue);

        return compact('transfer', 'jobNo', 'queueNo', 'barcodeValue', 'barcodePng');
    }
}
