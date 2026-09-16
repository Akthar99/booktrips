<?php

use App\Jobs\WatermarkPackageImage;
use App\Models\Business;
use App\Models\User;
use App\Services\ImageWatermarker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => true]);
});

it('queues a watermark job for every uploaded photo', function () {
    Queue::fake();

    $response = $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [
            UploadedFile::fake()->image('ridge.jpg', 900, 600),
            UploadedFile::fake()->image('dinner.jpg', 900, 600),
        ],
    ]);

    $response->assertOk()->assertJsonCount(2, 'images');

    Queue::assertPushed(WatermarkPackageImage::class, 2);
    Queue::assertPushed(WatermarkPackageImage::class, fn (WatermarkPackageImage $job): bool => $job->disk === 'public'
        && str_starts_with($job->path, "packages/{$this->business->id}/"));
});

it('refuses photos larger than six megabytes', function () {
    $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->create('huge.jpg', 6145, 'image/jpeg')],
    ])->assertSessionHasErrors('images.0');

    $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->create('fine.jpg', 6100, 'image/jpeg')],
    ])->assertOk();
});

it('stamps the configured watermark onto an image', function () {
    $path = Storage::disk('public')->path('packages/test/photo.png');

    Storage::disk('public')->makeDirectory('packages/test');

    $image = imagecreatetruecolor(600, 400);
    imagefilledrectangle($image, 0, 0, 600, 400, imagecolorallocate($image, 120, 160, 140));
    imagepng($image, $path);
    imagedestroy($image);

    $before = md5_file($path);

    $applied = app(ImageWatermarker::class)->apply($path);

    expect($applied)->toBeTrue()
        ->and(md5_file($path))->not->toBe($before)
        ->and(getimagesize($path)[0])->toBe(600);
});

it('leaves the file alone when watermarking is switched off', function () {
    config(['booktrips.uploads.watermark.enabled' => false]);

    $path = Storage::disk('public')->path('packages/test/plain.png');

    Storage::disk('public')->makeDirectory('packages/test');

    $image = imagecreatetruecolor(120, 90);
    imagepng($image, $path);
    imagedestroy($image);

    $before = md5_file($path);

    expect(app(ImageWatermarker::class)->apply($path))->toBeFalse()
        ->and(md5_file($path))->toBe($before);
});
