<?php

use App\Services\MediaUrl;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('s3', ['url' => 'https://booktips-bucket.s3.ap-southeast-1.amazonaws.com']);

    config([
        'booktrips.storage.images_disk' => 's3',
        'filesystems.disks.s3.bucket' => 'booktips-bucket',
        'filesystems.disks.s3.region' => 'ap-southeast-1',
    ]);
});

it('resolves legacy storage paths, bare keys and absolute urls', function () {
    $media = app(MediaUrl::class);

    expect($media->url('/storage/packages/3/old.jpg'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/old.jpg')
        ->and($media->url('packages/3/key.jpg'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/key.jpg')
        ->and($media->url('https://example.com/external.jpg'))
        ->toBe('https://example.com/external.jpg')
        ->and($media->url(''))->toBeNull()
        ->and($media->url(null))->toBeNull();
});

it('only extracts keys for objects it owns', function () {
    $media = app(MediaUrl::class);

    expect($media->key('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/old.jpg'))
        ->toBe('packages/3/old.jpg')
        ->and($media->key('/storage/packages/3/old.jpg'))
        ->toBe('packages/3/old.jpg')
        ->and($media->key('https://example.com/external.jpg'))
        ->toBeNull()
        ->and($media->key('/img/logo.png'))
        ->toBeNull();
});

it('forgets images on the configured disk and ignores external links', function () {
    Storage::disk('s3')->put('packages/3/old.jpg', 'x');
    Storage::disk('s3')->put('packages/3/keep.jpg', 'y');

    $media = app(MediaUrl::class);

    $media->forget('/storage/packages/3/old.jpg');
    $media->forget('https://example.com/external.jpg');

    Storage::disk('s3')->assertMissing('packages/3/old.jpg');
    Storage::disk('s3')->assertExists('packages/3/keep.jpg');
});

it('deletes only the images that left a package', function () {
    Storage::disk('s3')->put('packages/3/a.jpg', 'a');
    Storage::disk('s3')->put('packages/3/b.jpg', 'b');

    app(MediaUrl::class)->deleteRemoved(
        ['packages/3/a.jpg', 'packages/3/b.jpg'],
        ['packages/3/b.jpg'],
    );

    Storage::disk('s3')->assertMissing('packages/3/a.jpg');
    Storage::disk('s3')->assertExists('packages/3/b.jpg');
});
