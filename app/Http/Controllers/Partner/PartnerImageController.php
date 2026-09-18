<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\StoreImagesRequest;
use App\Services\ImageWatermarker;
use App\Services\MediaUrl;
use Illuminate\Http\JsonResponse;

class PartnerImageController extends Controller
{
    public function __construct(
        private readonly ImageWatermarker $watermarker,
        private readonly MediaUrl $media,
    ) {}

    /**
     * Upload package photos and return their public URLs.
     *
     * The watermark is stamped onto the local upload *before* it is stored, so
     * S3 receives one watermarked object in a single pass — no re-download and
     * no queue worker dependency. Partners wait only for GD + the transfer.
     */
    public function store(StoreImagesRequest $request): JsonResponse
    {
        $business = $request->user()->business;
        $disk = $this->media->disk();

        $urls = collect($request->file('images', []))
            ->map(function ($file) use ($business, $disk): ?string {
                $local = $file->getRealPath();

                if ($local !== false) {
                    $this->watermarker->apply($local);
                }

                $path = $file->store("packages/{$business->id}", $disk);

                return $path === false ? null : $this->media->url($path);
            })
            ->filter()
            ->values()
            ->all();

        return response()->json(['images' => $urls]);
    }
}
