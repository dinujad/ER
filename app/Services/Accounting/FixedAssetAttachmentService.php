<?php

namespace App\Services\Accounting;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaAssetAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FixedAssetAttachmentService
{
    /** @var array<int, string> */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private const MAX_BYTES = 10 * 1024 * 1024;

    public static function store(FaAsset $asset, UploadedFile $file, array $meta, ?int $userId = null): FaAssetAttachment
    {
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('File type not allowed.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('File exceeds 10MB limit.');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['exe', 'bat', 'cmd', 'com', 'msi'], true)) {
            throw new RuntimeException('Executable files are not allowed.');
        }

        $dir = 'fa-attachments/'.$asset->id;
        $path = $file->store($dir, 'local');

        $attachment = FaAssetAttachment::create([
            'asset_id' => $asset->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'description' => $meta['description'] ?? null,
            'document_type' => $meta['document_type'] ?? null,
            'uploaded_by' => $userId,
        ]);

        AccountingAuditService::record(
            'fa_attachment.uploaded',
            $attachment,
            null,
            $attachment->toArray(),
            null,
            (int) $asset->business_id,
            $userId
        );

        return $attachment;
    }

    public static function delete(FaAssetAttachment $attachment, ?int $userId = null): void
    {
        Storage::disk($attachment->disk ?: 'local')->delete($attachment->path);
        $before = $attachment->toArray();
        $attachment->delete();
        AccountingAuditService::record(
            'fa_attachment.deleted',
            $attachment,
            $before,
            null,
            null,
            0,
            $userId
        );
    }
}