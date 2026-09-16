<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptDownloadController extends Controller
{
    /**
     * Download a private receipt file (owner partner or admin only).
     */
    public function __invoke(Receipt $receipt): StreamedResponse
    {
        $this->authorize('view', $receipt);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($disk->exists($receipt->file_path), 404);

        return $disk->download(
            $receipt->file_path,
            $receipt->original_name ?: 'receipt-'.$receipt->id,
        );
    }
}
