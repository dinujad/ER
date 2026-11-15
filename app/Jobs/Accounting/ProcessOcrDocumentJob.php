<?php

namespace App\Jobs\Accounting;

use App\Services\Accounting\Ocr\OcrExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOcrDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $documentId) {}

    public function handle(OcrExtractionService $ocr): void
    {
        $ocr->process($this->documentId);
    }
}
