<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReceiptDownloadController extends Controller
{
    /**
     * Download a private receipt file (owner partner or admin only).
     */
    public function __invoke(Receipt $receipt): Response
    {
        $this->authorize('view', $receipt);

        $diskName = (string) config('booktrips.storage.receipts_disk', 'local');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($receipt->file_path), 404);

        // Remote disks (S3) hand out a short-lived signed URL instead of
        // tunnelling every receipt through PHP.
        if ($diskName !== 'local') {
            return redirect()->away($disk->temporaryUrl($receipt->file_path, now()->addMinutes(5)));
        }

        return $disk->download(
            $receipt->file_path,
            $receipt->original_name ?: 'receipt-'.$receipt->id,
        );
    }
}
