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

it('rescues scheme-less paths written while AWS_URL was blank', function () {
    // A blank `AWS_URL=` in the environment made Flysystem return
    // "/packages/..." for every upload — those rows must still resolve.
    $media = app(MediaUrl::class);

    expect($media->url('/packages/9/photo.jpg'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/9/photo.jpg')
        ->and($media->url('/receipts/9/slip.pdf'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/receipts/9/slip.pdf')
        ->and($media->url('/img/logo.png'))
        ->toBe('/img/logo.png');
});

it('rewrites localhost and app-domain urls onto the current disk', function () {
    config(['app.url' => 'https://booktrips.lk']);

    $media = app(MediaUrl::class);

    expect($media->url('http://localhost:8000/storage/packages/3/old.jpg'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/old.jpg')
        ->and($media->url('https://booktrips.lk/storage/packages/3/old.jpg'))
        ->toBe('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/old.jpg');
});

it('never lets a blank AWS_URL produce scheme-less urls', function () {
    // phpunit.xml pins AWS_URL to an empty string: the config must turn that
    // into null, otherwise Flysystem returns "/packages/..." paths.
    $diskConfig = require config_path('filesystems.php');

    expect(env('AWS_URL'))->toBe('')
        ->and($diskConfig['disks']['s3']['url'])->toBeNull();
});

it('only extracts keys for objects it owns', function () {
    $media = app(MediaUrl::class);

    expect($media->key('https://booktips-bucket.s3.ap-southeast-1.amazonaws.com/packages/3/old.jpg'))
        ->toBe('packages/3/old.jpg')
        ->and($media->key('/storage/packages/3/old.jpg'))
        ->toBe('packages/3/old.jpg')
        ->and($media->key('/packages/3/old.jpg'))
        ->toBe('packages/3/old.jpg')
        ->and($media->key('http://localhost:8000/storage/packages/3/old.jpg'))
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
