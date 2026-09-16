<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\StoreImagesRequest;
use App\Jobs\WatermarkPackageImage;
use Illuminate\Http\JsonResponse;

class PartnerImageController extends Controller
{
    /**
     * Upload package photos to the public disk and return their URLs.
     *
     * The BookTrips watermark is applied by a queued job afterwards so the
     * partner is not kept waiting for image processing.
     */
    public function store(StoreImagesRequest $request): JsonResponse
    {
        $business = $request->user()->business;

        $urls = collect($request->file('images', []))
            ->map(function ($file) use ($business): string {
                $path = $file->store("packages/{$business->id}", 'public');

                WatermarkPackageImage::dispatch('public', $path);

                return '/storage/'.$path;
            })
            ->values()
            ->all();

        return response()->json(['images' => $urls]);
    }
}
