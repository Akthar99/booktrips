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
     * The BookTrips watermark is applied before the response is sent, so the
     * returned URLs always point at the branded image — no queue worker required.
     */
    public function store(StoreImagesRequest $request): JsonResponse
    {
        $business = $request->user()->business;

        $urls = collect($request->file('images', []))
            ->map(function ($file) use ($business): string {
                $path = $file->store("packages/{$business->id}", 'public');

                WatermarkPackageImage::dispatchSync('public', $path);

                return '/storage/'.$path;
            })
            ->values()
            ->all();

        return response()->json(['images' => $urls]);
    }
}
