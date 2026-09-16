<?php

namespace App\Jobs;

use App\Services\ImageWatermarker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Brands an uploaded package photo after the request has already responded,
 * so partners never wait for image processing.
 */
class WatermarkPackageImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $disk,
        public string $path,
    ) {}

    public function handle(ImageWatermarker $watermarker): void
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($this->path)) {
            return;
        }

        try {
            $absolute = $storage->path($this->path);
        } catch (Throwable $exception) {
            // Remote disks have no local path; watermarking is skipped rather than failing the job.
            Log::info("Watermark skipped for {$this->path}: {$exception->getMessage()}");

            return;
        }

        $watermarker->apply($absolute);
    }
}
