<?php

use App\Models\Business;
use App\Models\User;
use App\Services\ImageWatermarker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://booktips-bucket.s3.ap-southeast-1.amazonaws.com']);

    config([
        'booktrips.storage.images_disk' => 'public',
        'booktrips.storage.receipts_disk' => 'local',
        'filesystems.disks.s3.bucket' => 'booktips-bucket',
        'filesystems.disks.s3.region' => 'ap-southeast-1',
    ]);

    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => true]);
});

it('watermarks every uploaded photo before storing it', function () {
    $uploads = [
        UploadedFile::fake()->image('ridge.jpg', 900, 600),
        UploadedFile::fake()->image('dinner.jpg', 900, 600),
    ];

    $originals = array_map(
        fn (UploadedFile $file): string => (string) md5_file((string) $file->getRealPath()),
        $uploads,
    );

    $response = $this->actingAs($this->partner)->post('/partners/images', ['images' => $uploads]);

    $response->assertOk()->assertJsonCount(2, 'images');

    foreach ($response->json('images') as $url) {
        expect($url)->toBeString()->toContain("/packages/{$this->business->id}/");
    }

    $stored = Storage::disk('public')->allFiles("packages/{$this->business->id}");
    $hashes = array_map(
        fn (string $path): string => (string) md5_file((string) Storage::disk('public')->path($path)),
        $stored,
    );

    expect($stored)->toHaveCount(2)
        ->and(array_intersect($hashes, $originals))->toBeEmpty();
});

it('stores photos on the s3 disk when configured', function () {
    config(['booktrips.storage.images_disk' => 's3']);

    $upload = UploadedFile::fake()->image('beach.jpg', 900, 600);
    $original = (string) md5_file((string) $upload->getRealPath());

    $response = $this->actingAs($this->partner)->post('/partners/images', ['images' => [$upload]]);

    $response->assertOk()->assertJsonCount(1, 'images');

    $stored = Storage::disk('s3')->allFiles("packages/{$this->business->id}");

    expect($stored)->toHaveCount(1)
        ->and((string) md5_file((string) Storage::disk('s3')->path($stored[0])))->not->toBe($original)
        ->and($response->json('images.0'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/'.$stored[0]);
});

it('refuses photos larger than six megabytes', function () {
    $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->create('huge.jpg', 6145, 'image/jpeg')],
    ])->assertSessionHasErrors('images.0');

    $this->actingAs($this->partner)->post('/partners/images', [
        'images' => [UploadedFile::fake()->create('fine.jpg', 6100, 'image/jpeg')],
    ])->assertOk();
});

it('refuses more than twenty-five photos in one upload', function () {
    $images = array_map(
        fn (int $index) => UploadedFile::fake()->image("photo-{$index}.jpg", 80, 60),
        range(1, 26),
    );

    $this->actingAs($this->partner)->post('/partners/images', ['images' => $images])
        ->assertSessionHasErrors('images');
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
