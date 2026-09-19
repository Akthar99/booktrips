<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Single place that turns stored image values into browsable URLs.
 *
 * Values may come in four shapes because listings predate the S3 move:
 *  - an absolute URL (uploaded after the S3 migration),
 *  - a legacy "/storage/packages/..." path (local-disk era),
 *  - a scheme-less "/packages/..." path (saved while AWS_URL was set blank),
 *  - a bare storage key ("packages/...").
 */
class MediaUrl
{
    /**
     * The disk new photos are stored on.
     */
    public function disk(): string
    {
        return (string) config('booktrips.storage.images_disk', 'public');
    }

    /**
     * Resolve a stored value to a browsable URL.
     */
    public function url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            // URLs that point back at us (localhost, the app domain, the bucket)
            // are rewritten through the current disk so they keep working after
            // a bucket/CDN change. Truly external URLs (e.g. Unsplash) are left
            // untouched — they are not ours to manage.
            if ($this->isOwnedUrl($value)) {
                return $this->publicUrl($this->key($value) ?? ltrim((string) parse_url($value, PHP_URL_PATH), '/'));
            }

            return $value;
        }

        // Protocol-relative URLs ("//host/path") are already browsable.
        if (str_starts_with($value, '//')) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $this->publicUrl(substr($value, strlen('/storage/')));
        }

        // "/packages/..." rows were saved while a blank AWS_URL made Flysystem
        // return scheme-less paths. They are object keys, not web-root files,
        // so resolve them against the disk instead of serving a dead path.
        if (str_starts_with($value, '/')) {
            $key = ltrim($value, '/');

            return $this->isOwnedKey($key) ? $this->publicUrl($key) : $value;
        }

        return $this->publicUrl($value);
    }

    /**
     * Extract the storage key from a stored value, or null when the value
     * points somewhere we do not own (external links must never be deleted).
     */
    public function key(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '/storage/')) {
            return substr($value, strlen('/storage/'));
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            return $this->keyFromUrl($value);
        }

        if (str_starts_with($value, '//')) {
            return $this->keyFromUrl('https:'.$value);
        }

        if (str_starts_with($value, '/')) {
            $relative = ltrim($value, '/');

            return $this->isOwnedKey($relative) ? $relative : null;
        }

        return $value;
    }

    /**
     * Delete a stored image from the images disk.
     */
    public function forget(?string $value): void
    {
        $key = $this->key($value);

        if ($key !== null) {
            Storage::disk($this->disk())->delete($key);
        }
    }

    /**
     * Delete the images a package no longer uses.
     *
     * @param  array<int, string>  $before
     * @param  array<int, string>  $after
     */
    public function deleteRemoved(array $before, array $after): void
    {
        foreach (array_diff($before, $after) as $value) {
            $this->forget($value);
        }
    }

    /**
     * Build the public URL for a storage key.
     */
    public function publicUrl(string $key): string
    {
        $key = ltrim($key, '/');

        try {
            /** @var Cloud $disk */
            $disk = Storage::disk($this->disk());

            return (string) $disk->url($key);
        } catch (Throwable) {
            // Best-effort fallback when the host cannot generate URLs itself.
            $bucket = (string) config('filesystems.disks.s3.bucket');

            if ($this->disk() === 's3' && $bucket !== '') {
                return "https://{$bucket}.s3.{$this->region()}.amazonaws.com/{$key}";
            }

            return '/storage/'.$key;
        }
    }

    /**
     * Whether an absolute URL points at an object this application manages.
     */
    private function isOwnedUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && in_array($host, $this->ownedHosts(), true);
    }

    /**
     * Extract the object key from one of our own absolute URLs.
     */
    private function keyFromUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '' || ! in_array($host, $this->ownedHosts(), true)) {
            return null;
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $bucket = (string) config('filesystems.disks.s3.bucket');

        if ($bucket !== '' && str_starts_with($path, $bucket.'/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        return $path === '' ? null : $path;
    }

    /**
     * Whether a relative value is one of our object keys. Keeps root-relative
     * public assets ("/img/logo.png") out of bucket URL generation.
     */
    private function isOwnedKey(string $key): bool
    {
        return str_starts_with($key, 'packages/') || str_starts_with($key, 'receipts/');
    }

    /**
     * Hosts whose URLs resolve to files this application manages.
     *
     * @return array<int, string>
     */
    private function ownedHosts(): array
    {
        $hosts = ['localhost', '127.0.0.1', '::1', '[::1]'];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = strtolower($appHost);
        }

        $bucket = (string) config('filesystems.disks.s3.bucket');
        $region = $this->region();

        if ($bucket !== '') {
            $hosts[] = strtolower("{$bucket}.s3.{$region}.amazonaws.com");
            $hosts[] = strtolower("{$bucket}.s3.amazonaws.com");
            $hosts[] = "s3.{$region}.amazonaws.com";
            $hosts[] = 's3.amazonaws.com';
        }

        $cdnHost = parse_url((string) config('filesystems.disks.s3.url'), PHP_URL_HOST);

        if (is_string($cdnHost) && $cdnHost !== '') {
            $hosts[] = strtolower($cdnHost);
        }

        return array_values(array_unique($hosts));
    }

    /**
     * The configured bucket region, with a sane default for empty values.
     */
    private function region(): string
    {
        $region = (string) config('filesystems.disks.s3.region', 'us-east-1');

        return $region === '' ? 'us-east-1' : $region;
    }
}
