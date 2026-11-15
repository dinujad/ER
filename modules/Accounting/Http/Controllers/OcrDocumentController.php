<?php

namespace Modules\Accounting\Http\Controllers;

use App\Jobs\Accounting\ProcessOcrDocumentJob;
use App\Services\Accounting\Ocr\OcrExtractionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class OcrDocumentController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $docs = Schema::hasTable('acct_ocr_documents')
            ? DB::table('acct_ocr_documents')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->limit(50)
                ->get()
            : collect();

        return view('accounting::phase5.ocr.index', [
            'pageTitle' => 'Smart Document Capture',
            'pageSub' => 'OCR drafts · no auto-posting',
            'documents' => $docs,
        ]);
    }

    public function store(Request $request, OcrExtractionService $ocr)
    {
        $this->requireManage();
        $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'document_type' => 'nullable|in:supplier_invoice,purchase_bill,expense_receipt,credit_note,other',
        ]);

        $result = $ocr->upload(
            $this->sessionBusinessId(),
            Auth::id(),
            $request->file('document'),
            $request->input('document_type', 'supplier_invoice')
        );

        ProcessOcrDocumentJob::dispatch($result['document_id']);

        // Sync process in local/testing when queue is sync
        if (config('queue.default') === 'sync') {
            // job already ran
        }

        return redirect('accounting/phase5/ocr/'.$result['document_id'])
            ->with('status', 'Document uploaded and queued for extraction.');
    }

    public function show(int $id, OcrExtractionService $ocr)
    {
        $doc = DB::table('acct_ocr_documents')
            ->where('id', $id)
            ->where('business_id', $this->sessionBusinessId())
            ->first();
        if (! $doc) {
            abort(404);
        }
        $extraction = DB::table('acct_ocr_extractions')
            ->where('ocr_document_id', $id)
            ->orderByDesc('id')
            ->first();

        return view('accounting::phase5.ocr.show', [
            'pageTitle' => 'OCR Document #'.$id,
            'pageSub' => $doc->original_filename,
            'document' => $doc,
            'extraction' => $extraction,
            'fields' => $extraction ? (json_decode($extraction->extracted_fields ?? '{}', true) ?: []) : [],
        ]);
    }

    public function process(int $id, Request $request, OcrExtractionService $ocr)
    {
        $this->requireManage();
        $doc = DB::table('acct_ocr_documents')
            ->where('id', $id)
            ->where('business_id', $this->sessionBusinessId())
            ->first();
        if (! $doc) {
            abort(404);
        }

        $forced = null;
        if ($request->filled('forced_json') && app()->environment(['local', 'testing'])) {
            $forced = json_decode($request->input('forced_json'), true);
        }

        $ocr->process($id, $forced);

        return redirect('accounting/phase5/ocr/'.$id)->with('status', 'Extraction completed.');
    }

    public function createDraft(int $id, Request $request, OcrExtractionService $ocr)
    {
        $this->requireManage();
        $fields = $request->input('fields', []);
        if (! is_array($fields)) {
            $fields = [];
        }
        $result = $ocr->createDraft($id, $this->sessionBusinessId(), $fields, Auth::id());

        return redirect('accounting/phase5/ocr/'.$id)
            ->with('status', 'Draft created (not posted). Type: '.$result['draft_type']);
    }

    public function download(int $id)
    {
        $doc = DB::table('acct_ocr_documents')
            ->where('id', $id)
            ->where('business_id', $this->sessionBusinessId())
            ->first();
        if (! $doc) {
            abort(404);
        }

        return Storage::disk($doc->storage_disk)->download($doc->storage_path, $doc->original_filename);
    }
}
