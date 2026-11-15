<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetAttachment;
use App\Services\Accounting\FixedAssetAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class FixedAssetAttachmentController extends Controller
{
    use RequiresAccountingPermission;

    public function store(Request $request, int $assetId)
    {
        $this->requireManage();
        $asset = FaAsset::findOrFail($assetId);
        $request->validate([
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string|max:500',
            'document_type' => 'nullable|string|max:60',
        ]);

        try {
            FixedAssetAttachmentService::store(
                $asset,
                $request->file('file'),
                $request->only('description', 'document_type'),
                Auth::id()
            );
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/fixed-assets/'.$assetId);
    }

    public function download(int $assetId, int $attachmentId)
    {
        $attachment = FaAssetAttachment::where('asset_id', $assetId)->findOrFail($attachmentId);

        return Storage::disk($attachment->disk ?: 'local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(int $assetId, int $attachmentId)
    {
        $this->requireManage();
        if (! Schema::hasTable('fa_asset_attachments')) {
            abort(404);
        }
        $attachment = FaAssetAttachment::where('asset_id', $assetId)->findOrFail($attachmentId);
        try {
            FixedAssetAttachmentService::delete($attachment, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/fixed-assets/'.$assetId);
    }
}